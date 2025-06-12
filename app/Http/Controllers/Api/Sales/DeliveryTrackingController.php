<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SOLine;
use App\Models\Sales\SalesOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DeliveryTrackingController extends Controller
{
    /**
     * Get delivery dashboard data
     */
    public function getDashboard(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->endOfMonth()->format('Y-m-d'));

            // Get overdue deliveries
            $overdueQuery = SOLine::with(['salesOrder.customer', 'item'])
                ->whereNotNull('delivery_date')
                ->where('delivery_date', '<', now())
                ->whereHas('salesOrder', function ($q) {
                    $q->whereNotIn('status', ['Delivered', 'Invoiced', 'Closed']);
                });

            $overdue = $overdueQuery->get()->filter(function ($line) {
                return !$line->is_fully_delivered;
            });

            // Get deliveries due today
            $dueTodayQuery = SOLine::with(['salesOrder.customer', 'item'])
                ->whereDate('delivery_date', today())
                ->whereHas('salesOrder', function ($q) {
                    $q->whereNotIn('status', ['Delivered', 'Invoiced', 'Closed']);
                });

            $dueToday = $dueTodayQuery->get()->filter(function ($line) {
                return !$line->is_fully_delivered;
            });

            // Get deliveries due this week
            $dueThisWeekQuery = SOLine::with(['salesOrder.customer', 'item'])
                ->whereBetween('delivery_date', [now()->startOfWeek(), now()->endOfWeek()])
                ->whereHas('salesOrder', function ($q) {
                    $q->whereNotIn('status', ['Delivered', 'Invoiced', 'Closed']);
                });

            $dueThisWeek = $dueThisWeekQuery->get()->filter(function ($line) {
                return !$line->is_fully_delivered;
            });

            // Get upcoming deliveries (next 30 days)
            $upcomingQuery = SOLine::with(['salesOrder.customer', 'item'])
                ->whereBetween('delivery_date', [now()->addDay(), now()->addDays(30)])
                ->whereHas('salesOrder', function ($q) {
                    $q->whereNotIn('status', ['Delivered', 'Invoiced', 'Closed']);
                });

            $upcoming = $upcomingQuery->get()->filter(function ($line) {
                return !$line->is_fully_delivered;
            });

            // Get delivery statistics by customer
            $customerStats = SOLine::select([
                'customers.name as customer_name',
                'customers.customer_code',
                DB::raw('COUNT(*) as total_lines'),
                DB::raw('SUM(CASE WHEN SOLine.delivery_date < CURDATE() THEN 1 ELSE 0 END) as overdue_count'),
                DB::raw('SUM(CASE WHEN DATE(SOLine.delivery_date) = CURDATE() THEN 1 ELSE 0 END) as due_today_count')
            ])
                ->join('SalesOrder', 'SOLine.so_id', '=', 'SalesOrder.so_id')
                ->join('Customer as customers', 'SalesOrder.customer_id', '=', 'customers.customer_id')
                ->whereNotNull('SOLine.delivery_date')
                ->whereBetween('SOLine.delivery_date', [$dateFrom, $dateTo])
                ->whereNotIn('SalesOrder.status', ['Delivered', 'Invoiced', 'Closed'])
                ->groupBy('customers.customer_id', 'customers.name', 'customers.customer_code')
                ->orderBy('overdue_count', 'desc')
                ->get();

            // Get delivery trend data (last 30 days)
            $trendData = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dateStr = $date->format('Y-m-d');

                $scheduledCount = SOLine::whereDate('delivery_date', $date)->count();
                $overdueCount = SOLine::where('delivery_date', '<', $date)
                    ->whereDoesntHave('deliveryLines', function ($q) {
                        $q->havingRaw('SUM(quantity) >= SOLine.quantity');
                    })
                    ->count();

                $trendData[] = [
                    'date' => $dateStr,
                    'scheduled' => $scheduledCount,
                    'overdue' => $overdueCount
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'overdue_count' => $overdue->count(),
                        'due_today_count' => $dueToday->count(),
                        'due_this_week_count' => $dueThisWeek->count(),
                        'upcoming_count' => $upcoming->count()
                    ],
                    'overdue_deliveries' => $overdue->values(),
                    'due_today' => $dueToday->values(),
                    'due_this_week' => $dueThisWeek->values(),
                    'upcoming_deliveries' => $upcoming->take(20)->values(),
                    'customer_statistics' => $customerStats,
                    'trend_data' => $trendData
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting delivery dashboard: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get delivery dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed delivery schedule
     */
    public function getDeliverySchedule(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', now()->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->addDays(30)->format('Y-m-d'));
            $customerId = $request->get('customer_id');
            $status = $request->get('status', 'pending'); // pending, overdue, all

            $query = SOLine::with([
                'salesOrder.customer',
                'item',
                'unitOfMeasure',
                'deliveryLines'
            ])
                ->whereNotNull('delivery_date')
                ->whereBetween('delivery_date', [$dateFrom, $dateTo])
                ->whereHas('salesOrder', function ($q) use ($customerId) {
                    $q->whereNotIn('status', ['Delivered', 'Invoiced', 'Closed']);
                    if ($customerId) {
                        $q->where('customer_id', $customerId);
                    }
                });

            // Apply status filter
            if ($status === 'overdue') {
                $query->where('delivery_date', '<', now());
            } elseif ($status === 'due_today') {
                $query->whereDate('delivery_date', today());
            } elseif ($status === 'upcoming') {
                $query->where('delivery_date', '>', now());
            }

            $lines = $query->orderBy('delivery_date', 'asc')
                ->orderBy('so_id', 'asc')
                ->get();

            // Filter out fully delivered lines
            $pendingLines = $lines->filter(function ($line) {
                return !$line->is_fully_delivered;
            });

            // Group by delivery date
            $groupedByDate = $pendingLines->groupBy(function ($line) {
                return $line->delivery_date->format('Y-m-d');
            });

            $schedule = [];
            foreach ($groupedByDate as $date => $dateLines) {
                $schedule[] = [
                    'delivery_date' => $date,
                    'formatted_date' => Carbon::parse($date)->format('D, M j, Y'),
                    'is_overdue' => Carbon::parse($date)->isPast(),
                    'is_today' => Carbon::parse($date)->isToday(),
                    'lines_count' => $dateLines->count(),
                    'lines' => $dateLines->map(function ($line) {
                        return [
                            'line_id' => $line->line_id,
                            'so_number' => $line->salesOrder->so_number,
                            'customer_name' => $line->salesOrder->customer->name,
                            'customer_code' => $line->salesOrder->customer->customer_code,
                            'item_code' => $line->item->item_code,
                            'item_name' => $line->item->name,
                            'quantity' => $line->quantity,
                            'delivered_quantity' => $line->deliveryLines->sum('quantity'),
                            'remaining_quantity' => $line->remaining_quantity,
                            'uom_symbol' => $line->unitOfMeasure->symbol,
                            'delivery_status' => $line->delivery_status,
                            'is_overdue' => $line->is_overdue,
                            'days_to_delivery' => $line->days_to_delivery
                        ];
                    })->values()
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'schedule' => $schedule,
                    'summary' => [
                        'total_lines' => $pendingLines->count(),
                        'total_customers' => $pendingLines->pluck('salesOrder.customer.customer_id')->unique()->count(),
                        'date_range' => [
                            'from' => $dateFrom,
                            'to' => $dateTo
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting delivery schedule: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get delivery schedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update delivery date for a specific line
     */
    public function updateDeliveryDate(Request $request, $lineId)
    {
        try {
            $request->validate([
                'delivery_date' => 'required|date|after_or_equal:today',
                'reason' => 'nullable|string|max:500'
            ]);

            $line = SOLine::with('salesOrder')->find($lineId);

            if (!$line) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sales order line not found'
                ], 404);
            }

            // Check if order can be modified
            if (in_array($line->salesOrder->status, ['Delivered', 'Invoiced', 'Closed'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot update delivery date for ' . $line->salesOrder->status . ' order'
                ], 400);
            }

            // Validate delivery date is not before SO date
            if ($request->delivery_date < $line->salesOrder->so_date) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Delivery date cannot be before order date'
                ], 422);
            }

            $oldDeliveryDate = $line->delivery_date;
            $line->delivery_date = $request->delivery_date;
            $line->save();

            // Log the change if needed
            Log::info("Delivery date updated for SO Line {$lineId}", [
                'so_number' => $line->salesOrder->so_number,
                'old_date' => $oldDeliveryDate,
                'new_date' => $request->delivery_date,
                'reason' => $request->reason
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Delivery date updated successfully',
                'data' => [
                    'line_id' => $line->line_id,
                    'old_delivery_date' => $oldDeliveryDate,
                    'new_delivery_date' => $line->delivery_date,
                    'so_number' => $line->salesOrder->so_number
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating delivery date: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update delivery date',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update delivery dates
     */
    public function bulkUpdateDeliveryDates(Request $request)
    {
        try {
            $request->validate([
                'line_ids' => 'required|array|min:1',
                'line_ids.*' => 'exists:SOLine,line_id',
                'delivery_date' => 'required|date|after_or_equal:today',
                'reason' => 'nullable|string|max:500'
            ]);

            $lineIds = $request->line_ids;
            $newDeliveryDate = $request->delivery_date;

            $lines = SOLine::with('salesOrder')
                ->whereIn('line_id', $lineIds)
                ->get();

            $updatedCount = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($lines as $line) {
                try {
                    // Check if order can be modified
                    if (in_array($line->salesOrder->status, ['Delivered', 'Invoiced', 'Closed'])) {
                        $errors[] = "Cannot update line {$line->line_id} - order {$line->salesOrder->so_number} is {$line->salesOrder->status}";
                        continue;
                    }

                    // Validate delivery date is not before SO date
                    if ($newDeliveryDate < $line->salesOrder->so_date) {
                        $errors[] = "Cannot update line {$line->line_id} - delivery date cannot be before order date";
                        continue;
                    }

                    $line->delivery_date = $newDeliveryDate;
                    $line->save();
                    $updatedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Error updating line {$line->line_id}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Successfully updated {$updatedCount} delivery dates",
                'data' => [
                    'updated_count' => $updatedCount,
                    'total_requested' => count($lineIds),
                    'errors' => $errors,
                    'new_delivery_date' => $newDeliveryDate
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error bulk updating delivery dates: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to bulk update delivery dates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get delivery performance analytics
     */
    public function getDeliveryAnalytics(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', now()->subMonths(3)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            // On-time delivery rate
            $totalDeliveries = SOLine::whereNotNull('delivery_date')
                ->whereBetween('delivery_date', [$dateFrom, $dateTo])
                ->whereHas('deliveryLines')
                ->count();

            $onTimeDeliveries = SOLine::whereNotNull('delivery_date')
                ->whereBetween('delivery_date', [$dateFrom, $dateTo])
                ->whereHas('deliveryLines', function ($q) {
                    $q->where('delivery_date', '<=', DB::raw('SOLine.delivery_date'));
                })
                ->count();

            $onTimeRate = $totalDeliveries > 0 ? ($onTimeDeliveries / $totalDeliveries) * 100 : 0;

            // Average delivery delay
            $avgDelay = SOLine::select(DB::raw('AVG(DATEDIFF(actual_delivery.delivery_date, SOLine.delivery_date)) as avg_delay'))
                ->join('DeliveryLine as dl', 'SOLine.line_id', '=', 'dl.so_line_id')
                ->join('Delivery as actual_delivery', 'dl.delivery_id', '=', 'actual_delivery.delivery_id')
                ->whereNotNull('SOLine.delivery_date')
                ->whereBetween('SOLine.delivery_date', [$dateFrom, $dateTo])
                ->value('avg_delay');

            // Performance by customer
            $customerPerformance = SOLine::select([
                'customers.name as customer_name',
                'customers.customer_code',
                DB::raw('COUNT(*) as total_lines'),
                DB::raw('SUM(CASE WHEN actual_delivery.delivery_date <= SOLine.delivery_date THEN 1 ELSE 0 END) as on_time_count'),
                DB::raw('ROUND(AVG(DATEDIFF(actual_delivery.delivery_date, SOLine.delivery_date)), 2) as avg_delay_days')
            ])
                ->join('SalesOrder', 'SOLine.so_id', '=', 'SalesOrder.so_id')
                ->join('Customer as customers', 'SalesOrder.customer_id', '=', 'customers.customer_id')
                ->join('DeliveryLine as dl', 'SOLine.line_id', '=', 'dl.so_line_id')
                ->join('Delivery as actual_delivery', 'dl.delivery_id', '=', 'actual_delivery.delivery_id')
                ->whereNotNull('SOLine.delivery_date')
                ->whereBetween('SOLine.delivery_date', [$dateFrom, $dateTo])
                ->groupBy('customers.customer_id', 'customers.name', 'customers.customer_code')
                ->having('total_lines', '>=', 5) // Only customers with at least 5 deliveries
                ->orderBy('on_time_count', 'desc')
                ->get()
                ->map(function ($item) {
                    $item->on_time_rate = $item->total_lines > 0 ? ($item->on_time_count / $item->total_lines) * 100 : 0;
                    return $item;
                });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_deliveries' => $totalDeliveries,
                        'on_time_deliveries' => $onTimeDeliveries,
                        'on_time_rate' => round($onTimeRate, 2),
                        'average_delay_days' => round($avgDelay ?? 0, 2)
                    ],
                    'customer_performance' => $customerPerformance,
                    'date_range' => [
                        'from' => $dateFrom,
                        'to' => $dateTo
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting delivery analytics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get delivery analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

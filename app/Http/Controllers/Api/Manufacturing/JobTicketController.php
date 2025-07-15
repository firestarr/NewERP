<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\JobTicket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JobTicketController extends Controller
{
    /**
     * Display a listing of job tickets.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = JobTicket::with(['productionOrder', 'productionOrder.workOrder']);

            // Apply search filters
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('item', 'like', "%{$searchTerm}%")
                        ->orWhere('ref_jo_no', 'like', "%{$searchTerm}%")
                        ->orWhere('customer', 'like', "%{$searchTerm}%")
                        ->orWhere('fgrn_no', 'like', "%{$searchTerm}%");
                });
            }

            // Apply date filters
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->where('date', '<=', $request->date_to);
            }

            // Apply customer filter
            if ($request->has('customer') && !empty($request->customer)) {
                $query->where('customer', 'like', "%{$request->customer}%");
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Apply pagination
            $perPage = $request->get('per_page', 15);
            $jobTickets = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $jobTickets,
                'message' => 'Job tickets retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving job tickets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created job ticket.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item' => 'required|string|max:255',
                'uom' => 'required|string|max:50',
                'qty_completed' => 'required|numeric|min:0',
                'ref_jo_no' => 'nullable|string|max:100',
                'issue_date_jo' => 'nullable|date',
                'qty_jo' => 'nullable|numeric|min:0',
                'customer' => 'nullable|string|max:255',
                'production_id' => 'nullable|exists:production_orders,production_id',
                'fgrn_no' => 'nullable|string|max:50',
                'date' => 'nullable|date',
            ]);

            $jobTicket = JobTicket::create($validated);
            $jobTicket->load(['productionOrder', 'productionOrder.workOrder']);

            return response()->json([
                'success' => true,
                'data' => $jobTicket,
                'message' => 'Job ticket created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating job ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified job ticket.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $jobTicket = JobTicket::with([
                'productionOrder',
                'productionOrder.workOrder',
                'productionOrder.workOrder.item',
                'productionOrder.workOrder.bom',
                'productionOrder.workOrder.routing',
                'customerRelation'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $jobTicket,
                'message' => 'Job ticket retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving job ticket: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified job ticket.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $jobTicket = JobTicket::findOrFail($id);

            $validated = $request->validate([
                'item' => 'sometimes|required|string|max:255',
                'uom' => 'sometimes|required|string|max:50',
                'qty_completed' => 'sometimes|required|numeric|min:0',
                'ref_jo_no' => 'nullable|string|max:100',
                'issue_date_jo' => 'nullable|date',
                'qty_jo' => 'nullable|numeric|min:0',
                'customer' => 'nullable|string|max:255',
                'production_id' => 'nullable|exists:production_orders,production_id',
                'fgrn_no' => 'nullable|string|max:50',
                'date' => 'nullable|date',
            ]);

            $jobTicket->update($validated);
            $jobTicket->load(['productionOrder', 'productionOrder.workOrder']);

            return response()->json([
                'success' => true,
                'data' => $jobTicket,
                'message' => 'Job ticket updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating job ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified job ticket.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $jobTicket = JobTicket::findOrFail($id);
            $jobTicket->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job ticket deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting job ticket: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get job ticket data formatted for printing.
     */
    public function print(string $id): JsonResponse
    {
        try {
            $jobTicket = JobTicket::with([
                'productionOrder',
                'productionOrder.workOrder',
                'productionOrder.workOrder.item',
                'productionOrder.workOrder.bom',
                'productionOrder.workOrder.routing',
                'customerRelation'
            ])->findOrFail($id);

            // Format data for printing
            $printData = [
                'job_ticket' => $jobTicket,
                'company_info' => [
                    'name' => config('app.name', 'Manufacturing Company'),
                    'address' => 'Company Address',
                    'phone' => 'Company Phone',
                    'email' => 'company@email.com'
                ],
                'print_date' => now()->format('Y-m-d H:i:s'),
                'print_user' => auth()->user()->name ?? 'System'
            ];

            return response()->json([
                'success' => true,
                'data' => $printData,
                'message' => 'Job ticket print data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving job ticket print data: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get job tickets statistics for dashboard.
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_tickets' => JobTicket::count(),
                'completed_today' => JobTicket::whereDate('date', today())->count(),
                'this_month' => JobTicket::whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)
                    ->count(),
                'total_qty_completed' => JobTicket::sum('qty_completed'),
                'unique_customers' => JobTicket::distinct('customer')->count('customer'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Job ticket statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}

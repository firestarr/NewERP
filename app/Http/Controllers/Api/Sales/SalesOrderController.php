<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SOLine;
use App\Models\Sales\Customer;
use App\Models\Items\Item;
use App\Models\UnitOfMeasure;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\Log;

class SalesOrderController extends Controller
{
    /**
     * Generate the next sales order number with format SO-yy-000000
     *
     * @return string
     */
    private function generateSalesOrderNumber()
    {
        $currentYear = date('y'); // Get 2-digit year
        $prefix = "SO-{$currentYear}-";

        // Get the latest sales order number for current year
        $latestSalesOrder = SalesOrder::where('so_number', 'like', $prefix . '%')
            ->orderBy('so_number', 'desc')
            ->first();

        if ($latestSalesOrder) {
            // Extract the sequence number from the latest sales order
            $lastNumber = intval(substr($latestSalesOrder->so_number, -6));
            $nextNumber = $lastNumber + 1;
        } else {
            // First sales order of the year
            $nextNumber = 1;
        }

        // Format with 6 digits, padded with zeros
        return $prefix . sprintf('%06d', $nextNumber);
    }

    /**
     * Get the next sales order number (for preview)
     *
     * @return \Illuminate\Http\Response
     */
    public function getNextSalesOrderNumber()
    {
        return response()->json([
            'next_so_number' => $this->generateSalesOrderNumber()
        ]);
    }

    /**
     * Display a listing of sales orders with search and filters
     */
    public function index(Request $request)
    {
        try {
            $query = SalesOrder::with(['customer', 'salesQuotation', 'deliveries', 'salesInvoices']);

            // Search functionality - includes po_number_customer
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('so_number', 'like', "%{$search}%")
                        ->orWhere('po_number_customer', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%{$search}%")
                                ->orWhere('customer_code', 'like', "%{$search}%");
                        });
                });
            }

            // Status filter
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Customer filter
            if ($request->has('customer_id') && $request->customer_id !== '') {
                $query->where('customer_id', $request->customer_id);
            }

            // Date range filters
            if ($request->has('date_range') && $request->date_range !== 'all') {
                switch ($request->date_range) {
                    case 'today':
                        $query->whereDate('so_date', today());
                        break;
                    case 'week':
                        $query->whereBetween('so_date', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereMonth('so_date', now()->month)
                            ->whereYear('so_date', now()->year);
                        break;
                }
            }

            // Custom date range
            if ($request->has('start_date') && $request->start_date !== '') {
                $query->where('so_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date !== '') {
                $query->where('so_date', '<=', $request->end_date);
            }

            // Sorting
            $sortField = $request->get('sort_field', 'so_id');
            $sortDirection = $request->get('sort_direction', 'desc');

            // Map frontend sort fields to database fields
            $sortFieldMap = [
                'so_number' => 'so_number',
                'po_number_customer' => 'po_number_customer',
                'so_date' => 'so_date',
                'expected_delivery' => 'expected_delivery',
                'status' => 'status',
                'total_amount' => 'total_amount'
            ];

            if (array_key_exists($sortField, $sortFieldMap)) {
                $query->orderBy($sortFieldMap[$sortField], $sortDirection);
            } else {
                $query->orderBy('so_id', 'desc');
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $orders = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching sales orders: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch sales orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created sales order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Removed so_number from validation since it's auto-generated
            'po_number_customer' => 'nullable|string|max:100',
            'so_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'quotation_id' => 'nullable|exists:SalesQuotation,quotation_id',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'expected_delivery' => 'nullable|date',
            'status' => 'required|string|max:50',
            'currency_code' => 'nullable|string|size:3',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.uom_id' => 'required|exists:unit_of_measures,uom_id',
            'lines.*.discount' => 'nullable|numeric|min:0',
            'lines.*.tax' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the customer to check for preferred currency
            $customer = Customer::find($request->customer_id);

            // Determine currency to use
            $currencyCode = $request->currency_code ?? $customer->preferred_currency ?? config('app.base_currency', 'USD');
            $baseCurrency = config('app.base_currency', 'USD');

            // Get exchange rate
            $exchangeRate = 1.0;

            if ($currencyCode !== $baseCurrency) {
                $rate = CurrencyRate::getCurrentRate($currencyCode, $baseCurrency, $request->so_date);

                if (!$rate) {
                    // Try reverse rate
                    $reverseRate = CurrencyRate::getCurrentRate($baseCurrency, $currencyCode, $request->so_date);
                    if ($reverseRate) {
                        $exchangeRate = 1 / $reverseRate;
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'No exchange rate found for the specified currency on the sales date'
                        ], 422);
                    }
                } else {
                    $exchangeRate = $rate;
                }
            }

            // Generate the sales order number automatically
            $soNumber = $this->generateSalesOrderNumber();

            // Create sales order
            $salesOrder = SalesOrder::create([
                'so_number' => $soNumber,
                'po_number_customer' => $request->po_number_customer,
                'so_date' => $request->so_date,
                'customer_id' => $request->customer_id,
                'quotation_id' => $request->quotation_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => $request->status,
                'total_amount' => 0,
                'tax_amount' => 0,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency' => $baseCurrency,
                'base_currency_total' => 0,
                'base_currency_tax' => 0
            ]);

            $totalAmount = 0;
            $taxAmount = 0;

            // Create order lines
            foreach ($request->lines as $lineData) {
                $item = Item::find($lineData['item_id']);
                $unitPrice = $lineData['unit_price'] ?? $item->sale_price ?? 0;
                $quantity = $lineData['quantity'];
                $discount = $lineData['discount'] ?? 0;
                $tax = $lineData['tax'] ?? 0;

                $subtotal = $unitPrice * $quantity;
                $lineTotal = $subtotal - $discount + $tax;

                SOLine::create([
                    'so_id' => $salesOrder->so_id,
                    'item_id' => $lineData['item_id'],
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'uom_id' => $lineData['uom_id'],
                    'discount' => $discount,
                    'tax' => $tax,
                    'subtotal' => $subtotal,
                    'total' => $lineTotal
                ]);

                $totalAmount += $lineTotal;
                $taxAmount += $tax;
            }

            // Update order totals
            $salesOrder->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'base_currency_total' => $totalAmount * $exchangeRate,
                'base_currency_tax' => $taxAmount * $exchangeRate
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sales order created successfully',
                'data' => $salesOrder->load(['customer', 'salesOrderLines.item'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating sales order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create sales order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified sales order
     */
    public function show($id)
    {
        try {
            $order = SalesOrder::with([
                'customer',
                'salesQuotation',
                'salesOrderLines.item',
                'salesOrderLines.unitOfMeasure',
                'salesOrderLines.deliveryLines',
                'deliveries',
                'salesInvoices'
            ])->find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sales order not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching sales order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch sales order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified sales order
     */
    public function update(Request $request, $id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sales order not found'
            ], 404);
        }

        // Check if order can be updated
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update a ' . $order->status . ' sales order'
            ], 400);
        }

        $validatorRules = [
            // so_number should not be updated, so removed from validation
            'po_number_customer' => 'nullable|string|max:100',
            'so_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'quotation_id' => 'nullable|exists:SalesQuotation,quotation_id',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'expected_delivery' => 'nullable|date',
            'status' => 'required|string|max:50',
            'currency_code' => 'nullable|string|size:3',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.uom_id' => 'required|exists:unit_of_measures,uom_id',
            'lines.*.discount' => 'nullable|numeric|min:0',
            'lines.*.tax' => 'nullable|numeric|min:0',
        ];

        $validator = Validator::make($request->all(), $validatorRules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get customer and currency info
            $customer = Customer::find($request->customer_id);
            $currencyCode = $request->currency_code ?? $customer->preferred_currency ?? config('app.base_currency', 'USD');
            $baseCurrency = config('app.base_currency', 'USD');

            // Get exchange rate
            $exchangeRate = 1.0;

            if ($currencyCode !== $baseCurrency) {
                $rate = CurrencyRate::getCurrentRate($currencyCode, $baseCurrency, $request->so_date);

                if (!$rate) {
                    $reverseRate = CurrencyRate::getCurrentRate($baseCurrency, $currencyCode, $request->so_date);
                    if ($reverseRate) {
                        $exchangeRate = 1 / $reverseRate;
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'No exchange rate found for the specified currency on the sales date'
                        ], 422);
                    }
                } else {
                    $exchangeRate = $rate;
                }
            }

            // Update main order fields (excluding so_number)
            $order->update([
                'po_number_customer' => $request->po_number_customer,
                'so_date' => $request->so_date,
                'customer_id' => $request->customer_id,
                'quotation_id' => $request->quotation_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => $request->status,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency' => $baseCurrency
            ]);

            // Delete existing lines and create new ones
            $order->salesOrderLines()->delete();

            $totalAmount = 0;
            $taxAmount = 0;

            foreach ($request->lines as $lineData) {
                $item = Item::find($lineData['item_id']);
                $unitPrice = $lineData['unit_price'] ?? $item->sale_price ?? 0;
                $quantity = $lineData['quantity'];
                $discount = $lineData['discount'] ?? 0;
                $tax = $lineData['tax'] ?? 0;

                $subtotal = $unitPrice * $quantity;
                $lineTotal = $subtotal - $discount + $tax;

                SOLine::create([
                    'so_id' => $order->so_id,
                    'item_id' => $lineData['item_id'],
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'uom_id' => $lineData['uom_id'],
                    'discount' => $discount,
                    'tax' => $tax,
                    'subtotal' => $subtotal,
                    'total' => $lineTotal
                ]);

                $totalAmount += $lineTotal;
                $taxAmount += $tax;
            }

            // Update order totals
            $order->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'base_currency_total' => $totalAmount * $exchangeRate,
                'base_currency_tax' => $taxAmount * $exchangeRate
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sales order updated successfully',
                'data' => $order->load(['customer', 'salesOrderLines.item'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating sales order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update sales order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified sales order
     */
    public function destroy($id)
    {
        try {
            $order = SalesOrder::with(['deliveries', 'salesInvoices'])->find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sales order not found'
                ], 404);
            }

            // Check if order can be deleted
            if ($order->deliveries->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete sales order that has deliveries'
                ], 400);
            }

            if ($order->salesInvoices->count() > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot delete sales order that has invoices'
                ], 400);
            }

            DB::beginTransaction();

            // Delete order lines first
            $order->salesOrderLines()->delete();

            // Delete the order
            $order->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Sales order deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting sales order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete sales order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download Excel template for sales order import
     */
    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();

            // ===== SHEET 1: SALES ORDER HEADER =====
            $headerSheet = $spreadsheet->getActiveSheet();
            $headerSheet->setTitle('Sales Orders');

            // Header columns for Sales Order (removed SO Number from template since it's auto-generated)
            $headers = [
                'A1' => 'Customer Code*',
                'B1' => 'Customer PO Number',
                'C1' => 'SO Date*',
                'D1' => 'Payment Terms',
                'E1' => 'Delivery Terms',
                'F1' => 'Expected Delivery',
                'G1' => 'Status*',
                'H1' => 'Currency Code',
                'I1' => 'Notes'
            ];

            // Apply headers
            foreach ($headers as $cell => $value) {
                $headerSheet->setCellValue($cell, $value);
            }

            // Style header row
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '366092']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ];

            $headerSheet->getStyle('A1:I1')->applyFromArray($headerStyle);

            // Set column widths
            $columnWidths = ['A' => 15, 'B' => 15, 'C' => 12, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 12, 'H' => 12, 'I' => 25];
            foreach ($columnWidths as $column => $width) {
                $headerSheet->getColumnDimension($column)->setWidth($width);
            }

            // Add sample data
            $sampleData = [
                ['CUST001', 'PO-CUSTOMER-001', '2024-01-15', 'Net 30', 'FOB Destination', '2024-02-15', 'Draft', 'USD', 'Sample sales order 1'],
                ['CUST002', 'PO-CUSTOMER-002', '2024-01-16', 'Net 60', 'FOB Origin', '2024-02-20', 'Confirmed', 'EUR', 'Sample sales order 2']
            ];

            $row = 2;
            foreach ($sampleData as $data) {
                $col = 'A';
                foreach ($data as $value) {
                    $headerSheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // ===== SHEET 2: SALES ORDER LINES =====
            $linesSheet = $spreadsheet->createSheet();
            $linesSheet->setTitle('Sales Order Lines');

            $lineHeaders = [
                'A1' => 'Customer Code*',
                'B1' => 'Item Code*',
                'C1' => 'Quantity*',
                'D1' => 'UOM Code*',
                'E1' => 'Unit Price',
                'F1' => 'Discount',
                'G1' => 'Tax',
                'H1' => 'Notes'
            ];

            foreach ($lineHeaders as $cell => $value) {
                $linesSheet->setCellValue($cell, $value);
            }

            $linesSheet->getStyle('A1:H1')->applyFromArray($headerStyle);

            // Set column widths for lines sheet
            $lineColumnWidths = ['A' => 15, 'B' => 15, 'C' => 10, 'D' => 10, 'E' => 12, 'F' => 10, 'G' => 10, 'H' => 25];
            foreach ($lineColumnWidths as $column => $width) {
                $linesSheet->getColumnDimension($column)->setWidth($width);
            }

            // Add sample line data
            $sampleLineData = [
                ['CUST001', 'ITEM001', 10, 'PCS', 100.00, 0, 10.00, 'Line 1 for customer order'],
                ['CUST001', 'ITEM002', 5, 'KG', 50.00, 5.00, 2.50, 'Line 2 for customer order'],
                ['CUST002', 'ITEM003', 20, 'PCS', 75.00, 0, 15.00, 'Line 1 for customer order']
            ];

            $row = 2;
            foreach ($sampleLineData as $data) {
                $col = 'A';
                foreach ($data as $value) {
                    $linesSheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // ===== SHEET 3: INSTRUCTIONS =====
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');

            $instructions = [
                'SALES ORDER IMPORT INSTRUCTIONS',
                '',
                '1. GENERAL RULES:',
                '   - Fields marked with * are required',
                '   - Sales Order numbers will be auto-generated (SO-yy-000000 format)',
                '   - Use the exact codes from Reference Data sheet',
                '   - Date format: YYYY-MM-DD (e.g., 2024-01-15)',
                '   - Decimal numbers use dot (.) as separator',
                '',
                '2. SALES ORDERS SHEET:',
                '   - Customer Code: Must exist in system',
                '   - Customer PO Number: Optional field for customer\'s purchase order reference',
                '   - Status: Draft, Confirmed, Processing, Shipped, Delivered, Invoiced, Closed',
                '   - Currency Code: Use standard 3-letter codes (USD, EUR, etc.)',
                '',
                '3. SALES ORDER LINES SHEET:',
                '   - Customer Code: Must match exactly with Customer Code in Sales Orders sheet',
                '   - Item Code: Must exist and be sellable',
                '   - UOM Code: Must exist in system',
                '   - Unit Price: If empty, system will use default sale price',
                '   - Discount and Tax: Optional, use 0 if not applicable',
                '',
                '4. IMPORT PROCESS:',
                '   - System will auto-generate SO Numbers (SO-yy-000000 format)',
                '   - System will first create Sales Orders from "Sales Orders" sheet',
                '   - Then add lines from "Sales Order Lines" sheet',
                '   - Lines are matched to orders by Customer Code',
                '',
                '5. ERROR HANDLING:',
                '   - Invalid data will be logged with row number',
                '   - Import will continue for other valid rows',
                '   - Download error report after import for details'
            ];

            $row = 1;
            foreach ($instructions as $instruction) {
                $instructionSheet->setCellValue('A' . $row, $instruction);
                if ($row == 1) {
                    $instructionSheet->getStyle('A' . $row)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '366092']]
                    ]);
                } elseif (strpos($instruction, ':') !== false && !empty(trim($instruction))) {
                    $instructionSheet->getStyle('A' . $row)->applyFromArray([
                        'font' => ['bold' => true]
                    ]);
                }
                $row++;
            }
            $instructionSheet->getColumnDimension('A')->setWidth(80);

            // ===== SHEET 4: REFERENCE DATA =====
            $refSheet = $spreadsheet->createSheet();
            $refSheet->setTitle('Reference Data');

            // Status values
            $refSheet->setCellValue('A1', 'Status Values');
            $refSheet->getStyle('A1')->applyFromArray(['font' => ['bold' => true]]);
            $statuses = ['Draft', 'Confirmed', 'Processing', 'Shipped', 'Delivered', 'Invoiced', 'Closed'];
            $row = 2;
            foreach ($statuses as $status) {
                $refSheet->setCellValue('A' . $row, $status);
                $row++;
            }

            // Currency codes
            $refSheet->setCellValue('C1', 'Currency Codes');
            $refSheet->getStyle('C1')->applyFromArray(['font' => ['bold' => true]]);
            $currencies = ['USD', 'EUR', 'IDR', 'SGD', 'JPY', 'GBP', 'AUD'];
            $row = 2;
            foreach ($currencies as $currency) {
                $refSheet->setCellValue('C' . $row, $currency);
                $row++;
            }

            // Set active sheet back to first sheet
            $spreadsheet->setActiveSheetIndex(0);

            // Generate filename and save
            $filename = 'sales_order_import_template_' . date('Y-m-d_H-i-s') . '.xlsx';
            $tempPath = storage_path('app/temp/' . $filename);

            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Error generating template: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate template',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import sales orders from Excel file
     */
    public function importFromExcel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'update_existing' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $updateExisting = $request->get('update_existing', false);

            $spreadsheet = IOFactory::load($file->getPathname());

            // Get Sales Orders sheet
            $headerSheet = $spreadsheet->getSheetByName('Sales Orders');
            if (!$headerSheet) {
                return response()->json(['message' => 'Sales Orders sheet not found'], 422);
            }

            // Get Sales Order Lines sheet
            $linesSheet = $spreadsheet->getSheetByName('Sales Order Lines');
            if (!$linesSheet) {
                return response()->json(['message' => 'Sales Order Lines sheet not found'], 422);
            }

            $headerHighestRow = $headerSheet->getHighestRow();
            $linesHighestRow = $linesSheet->getHighestRow();

            if ($headerHighestRow < 2) {
                return response()->json(['message' => 'No sales order data found'], 422);
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $createdOrders = [];

            DB::beginTransaction();

            // Process Sales Orders (Headers)
            for ($row = 2; $row <= $headerHighestRow; $row++) {
                try {
                    $customerCode = trim($headerSheet->getCell('A' . $row)->getValue() ?? '');
                    $poNumberCustomer = trim($headerSheet->getCell('B' . $row)->getValue() ?? '');
                    $soDate = $headerSheet->getCell('C' . $row)->getFormattedValue();
                    $paymentTerms = trim($headerSheet->getCell('D' . $row)->getValue() ?? '');
                    $deliveryTerms = trim($headerSheet->getCell('E' . $row)->getValue() ?? '');
                    $expectedDelivery = $headerSheet->getCell('F' . $row)->getFormattedValue();
                    $status = trim($headerSheet->getCell('G' . $row)->getValue() ?? 'Draft');
                    $currencyCode = trim($headerSheet->getCell('H' . $row)->getValue() ?? 'USD');

                    // Skip empty rows
                    if (empty($customerCode)) {
                        continue;
                    }

                    // Validate required fields
                    if (empty($customerCode) || empty($soDate)) {
                        $errors[] = "Row {$row}: Missing required fields (Customer Code or SO Date)";
                        $errorCount++;
                        continue;
                    }

                    // Find customer
                    $customer = Customer::where('customer_code', $customerCode)->first();
                    if (!$customer) {
                        $errors[] = "Row {$row}: Customer with code '{$customerCode}' not found";
                        $errorCount++;
                        continue;
                    }

                    // Validate and convert dates
                    try {
                        $soDateFormatted = date('Y-m-d', strtotime($soDate));
                        $expectedDeliveryFormatted = !empty($expectedDelivery) ? date('Y-m-d', strtotime($expectedDelivery)) : null;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$row}: Invalid date format";
                        $errorCount++;
                        continue;
                    }

                    // Get exchange rate
                    $baseCurrency = config('app.base_currency', 'USD');
                    $exchangeRate = 1.0;

                    if ($currencyCode !== $baseCurrency) {
                        $rate = CurrencyRate::getCurrentRate($currencyCode, $baseCurrency, $soDateFormatted);
                        if (!$rate) {
                            $errors[] = "Row {$row}: No exchange rate found for {$currencyCode} to {$baseCurrency}";
                            $errorCount++;
                            continue;
                        }
                        $exchangeRate = $rate;
                    }

                    // Generate SO Number automatically
                    $soNumber = $this->generateSalesOrderNumber();

                    // Create Sales Order
                    $salesOrderData = [
                        'so_number' => $soNumber,
                        'po_number_customer' => $poNumberCustomer,
                        'so_date' => $soDateFormatted,
                        'customer_id' => $customer->customer_id,
                        'payment_terms' => $paymentTerms,
                        'delivery_terms' => $deliveryTerms,
                        'expected_delivery' => $expectedDeliveryFormatted,
                        'status' => $status,
                        'currency_code' => $currencyCode,
                        'exchange_rate' => $exchangeRate,
                        'base_currency' => $baseCurrency,
                        'total_amount' => 0,
                        'tax_amount' => 0,
                        'base_currency_total' => 0,
                        'base_currency_tax' => 0
                    ];

                    $salesOrder = SalesOrder::create($salesOrderData);
                    $createdOrders[$customerCode] = $salesOrder;
                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$row}: " . $e->getMessage();
                    $errorCount++;
                }
            }

            // Process Sales Order Lines
            if ($linesHighestRow >= 2) {
                for ($row = 2; $row <= $linesHighestRow; $row++) {
                    try {
                        $customerCode = trim($linesSheet->getCell('A' . $row)->getValue() ?? '');
                        $itemCode = trim($linesSheet->getCell('B' . $row)->getValue() ?? '');
                        $quantity = $linesSheet->getCell('C' . $row)->getValue();
                        $uomCode = trim($linesSheet->getCell('D' . $row)->getValue() ?? '');
                        $unitPrice = $linesSheet->getCell('E' . $row)->getValue();
                        $discount = $linesSheet->getCell('F' . $row)->getValue() ?? 0;
                        $tax = $linesSheet->getCell('G' . $row)->getValue() ?? 0;

                        // Skip empty rows
                        if (empty($customerCode) && empty($itemCode)) {
                            continue;
                        }

                        // Validate required fields
                        if (empty($customerCode) || empty($itemCode) || empty($quantity) || empty($uomCode)) {
                            $errors[] = "Line Row {$row}: Missing required fields";
                            $errorCount++;
                            continue;
                        }

                        // Check if Sales Order exists for this customer
                        if (!isset($createdOrders[$customerCode])) {
                            $errors[] = "Line Row {$row}: Sales Order for customer '{$customerCode}' not found in headers";
                            $errorCount++;
                            continue;
                        }

                        // Find item
                        $item = Item::where('item_code', $itemCode)->where('is_sellable', true)->first();
                        if (!$item) {
                            $errors[] = "Line Row {$row}: Item with code '{$itemCode}' not found or not sellable";
                            $errorCount++;
                            continue;
                        }

                        // Find UOM
                        $uom = UnitOfMeasure::where('code', $uomCode)->orWhere('symbol', $uomCode)->first();
                        if (!$uom) {
                            $errors[] = "Line Row {$row}: UOM with code '{$uomCode}' not found";
                            $errorCount++;
                            continue;
                        }

                        // Use provided unit price or default from item
                        $finalUnitPrice = !empty($unitPrice) ? $unitPrice : ($item->sale_price ?? 0);

                        $subtotal = $finalUnitPrice * $quantity;
                        $lineTotal = $subtotal - $discount + $tax;

                        // Create order line
                        SOLine::create([
                            'so_id' => $createdOrders[$customerCode]->so_id,
                            'item_id' => $item->item_id,
                            'unit_price' => $finalUnitPrice,
                            'quantity' => $quantity,
                            'uom_id' => $uom->uom_id,
                            'discount' => $discount,
                            'tax' => $tax,
                            'subtotal' => $subtotal,
                            'total' => $lineTotal
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = "Line Row {$row}: " . $e->getMessage();
                        $errorCount++;
                    }
                }
            }

            // Update order totals
            foreach ($createdOrders as $order) {
                $lines = $order->salesOrderLines;
                $totalAmount = $lines->sum('total');
                $taxAmount = $lines->sum('tax');

                $order->update([
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'base_currency_total' => $totalAmount * $order->exchange_rate,
                    'base_currency_tax' => $taxAmount * $order->exchange_rate
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Import completed',
                'summary' => [
                    'total_processed' => $successCount + $errorCount,
                    'successful' => $successCount,
                    'failed' => $errorCount,
                    'errors' => $errors
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export sales orders to Excel
     */
    public function exportToExcel(Request $request)
    {
        try {
            $query = SalesOrder::with(['customer', 'salesOrderLines.item', 'salesOrderLines.unitOfMeasure']);

            // Apply filters if provided
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->has('customer_id') && $request->customer_id !== '') {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->has('dateFrom') && $request->dateFrom !== '') {
                $query->where('so_date', '>=', $request->dateFrom);
            }

            if ($request->has('dateTo') && $request->dateTo !== '') {
                $query->where('so_date', '<=', $request->dateTo);
            }

            $salesOrders = $query->get();

            $spreadsheet = new Spreadsheet();

            // ===== SHEET 1: SALES ORDERS =====
            $orderSheet = $spreadsheet->getActiveSheet();
            $orderSheet->setTitle('Sales Orders');

            // Headers
            $headers = [
                'A1' => 'SO Number',
                'B1' => 'Customer PO Number',
                'C1' => 'SO Date',
                'D1' => 'Customer Code',
                'E1' => 'Customer Name',
                'F1' => 'Payment Terms',
                'G1' => 'Delivery Terms',
                'H1' => 'Expected Delivery',
                'I1' => 'Status',
                'J1' => 'Currency',
                'K1' => 'Total Amount',
                'L1' => 'Tax Amount'
            ];

            foreach ($headers as $cell => $value) {
                $orderSheet->setCellValue($cell, $value);
            }

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '366092']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ];
            $orderSheet->getStyle('A1:L1')->applyFromArray($headerStyle);

            // Add data
            $row = 2;
            foreach ($salesOrders as $order) {
                $orderSheet->setCellValue('A' . $row, $order->so_number);
                $orderSheet->setCellValue('B' . $row, $order->po_number_customer ?? '');
                $orderSheet->setCellValue('C' . $row, $order->so_date->format('Y-m-d'));
                $orderSheet->setCellValue('D' . $row, $order->customer->customer_code ?? '');
                $orderSheet->setCellValue('E' . $row, $order->customer->name);
                $orderSheet->setCellValue('F' . $row, $order->payment_terms);
                $orderSheet->setCellValue('G' . $row, $order->delivery_terms);
                $orderSheet->setCellValue('H' . $row, $order->expected_delivery ? $order->expected_delivery->format('Y-m-d') : '');
                $orderSheet->setCellValue('I' . $row, $order->status);
                $orderSheet->setCellValue('J' . $row, $order->currency_code ?? 'USD');
                $orderSheet->setCellValue('K' . $row, $order->total_amount);
                $orderSheet->setCellValue('L' . $row, $order->tax_amount);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'L') as $column) {
                $orderSheet->getColumnDimension($column)->setAutoSize(true);
            }

            // ===== SHEET 2: SALES ORDER LINES =====
            $linesSheet = $spreadsheet->createSheet();
            $linesSheet->setTitle('Sales Order Lines');

            $lineHeaders = [
                'A1' => 'SO Number',
                'B1' => 'Customer PO Number',
                'C1' => 'Item Code',
                'D1' => 'Item Name',
                'E1' => 'Quantity',
                'F1' => 'UOM',
                'G1' => 'Unit Price',
                'H1' => 'Discount',
                'I1' => 'Subtotal',
                'J1' => 'Tax',
                'K1' => 'Total'
            ];

            foreach ($lineHeaders as $cell => $value) {
                $linesSheet->setCellValue($cell, $value);
            }

            $linesSheet->getStyle('A1:K1')->applyFromArray($headerStyle);

            $row = 2;
            foreach ($salesOrders as $order) {
                foreach ($order->salesOrderLines as $line) {
                    $linesSheet->setCellValue('A' . $row, $order->so_number);
                    $linesSheet->setCellValue('B' . $row, $order->po_number_customer ?? '');
                    $linesSheet->setCellValue('C' . $row, $line->item->item_code);
                    $linesSheet->setCellValue('D' . $row, $line->item->name);
                    $linesSheet->setCellValue('E' . $row, $line->quantity);
                    $linesSheet->setCellValue('F' . $row, $line->unitOfMeasure->symbol);
                    $linesSheet->setCellValue('G' . $row, $line->unit_price);
                    $linesSheet->setCellValue('H' . $row, $line->discount);
                    $linesSheet->setCellValue('I' . $row, $line->subtotal);
                    $linesSheet->setCellValue('J' . $row, $line->tax);
                    $linesSheet->setCellValue('K' . $row, $line->total);
                    $row++;
                }
            }

            // Auto-size columns
            foreach (range('A', 'K') as $column) {
                $linesSheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Set active sheet
            $spreadsheet->setActiveSheetIndex(0);

            // Generate filename and save
            $filename = 'sales_orders_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $tempPath = storage_path('app/temp/' . $filename);

            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempPath);

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print sales order
     */
    public function print($id)
    {
        try {
            $order = SalesOrder::with([
                'customer',
                'salesOrderLines.item',
                'salesOrderLines.unitOfMeasure'
            ])->find($id);

            if (!$order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sales order not found'
                ], 404);
            }

            // Return view for printing
            return view('sales.orders.print', compact('order'));
        } catch (\Exception $e) {
            Log::error('Error printing sales order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to print sales order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sales order statistics
     */
    public function getStatistics(Request $request)
    {
        try {
            $query = SalesOrder::query();

            // Date range filter
            if ($request->has('date_from') && $request->date_from !== '') {
                $query->where('so_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to !== '') {
                $query->where('so_date', '<=', $request->date_to);
            }

            $statistics = [
                'total_orders' => $query->count(),
                'total_amount' => $query->sum('total_amount'),
                'by_status' => $query->groupBy('status')
                    ->selectRaw('status, count(*) as count, sum(total_amount) as total')
                    ->get(),
                'by_currency' => $query->groupBy('currency_code')
                    ->selectRaw('currency_code, count(*) as count, sum(total_amount) as total')
                    ->get(),
                'recent_orders' => SalesOrder::with('customer')
                    ->orderBy('so_date', 'desc')
                    ->limit(5)
                    ->get()
            ];

            return response()->json([
                'status' => 'success',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

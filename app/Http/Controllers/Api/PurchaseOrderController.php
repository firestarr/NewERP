<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\VendorQuotation;
use App\Models\Vendor;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Models\POLine;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\CurrencyRate;
use App\Http\Requests\PurchaseOrderRequest;
use App\Services\PONumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PurchaseOrderController extends Controller
{
    protected $poNumberGenerator;

    public function __construct(PONumberGenerator $poNumberGenerator)
    {
        $this->poNumberGenerator = $poNumberGenerator;
    }

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['vendor']);

        // Apply filters
        if ($request->filled('status')) {
            if (is_array($request->status)) {
                $query->whereIn('status', $request->status);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('po_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('po_date', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('po_number', 'like', "%{$search}%");
        }

        // Filter untuk Outstanding PO
        if ($request->has('has_outstanding') && $request->has_outstanding) {
            $query->whereHas('lines', function ($q) {
                $q->whereRaw('quantity > (
                    SELECT COALESCE(SUM(grl.received_quantity), 0)
                    FROM goods_receipt_lines grl
                    JOIN goods_receipts gr ON grl.receipt_id = gr.receipt_id
                    WHERE grl.po_line_id = po_lines.line_id
                    AND gr.status = \'confirmed\'
                )');
            });
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'po_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $purchaseOrders = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $purchaseOrders
        ]);
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(PurchaseOrderRequest $request)
    {
        try {
            DB::beginTransaction();

            // Generate PO number
            $poNumber = $this->poNumberGenerator->generate();

            // Get the vendor to check for default currency
            $vendor = Vendor::find($request->vendor_id);

            // Determine currency to use (from request, vendor preference, or system default)
            $currencyCode = $request->currency_code ?? $vendor->preferred_currency ?? config('app.base_currency', 'USD');
            $baseCurrency = config('app.base_currency', 'USD');

            // Get exchange rate
            $exchangeRate = 1.0; // Default for base currency

            if ($currencyCode !== $baseCurrency) {
                $rate = CurrencyRate::where('from_currency', $currencyCode)
                    ->where('to_currency', $baseCurrency)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', $request->po_date)
                    ->where(function ($query) use ($request) {
                        $query->where('end_date', '>=', $request->po_date)
                            ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();

                if (!$rate) {
                    // Try to find a reverse rate
                    $reverseRate = CurrencyRate::where('from_currency', $baseCurrency)
                        ->where('to_currency', $currencyCode)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $request->po_date)
                        ->where(function ($query) use ($request) {
                            $query->where('end_date', '>=', $request->po_date)
                                ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();

                    if ($reverseRate) {
                        $exchangeRate = 1 / $reverseRate->rate;
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No exchange rate found for the specified currency on the purchase date'
                        ], 422);
                    }
                } else {
                    $exchangeRate = $rate->rate;
                }
            }

            // Calculate totals in document currency
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }

            $totalAmount = $subtotal + $taxTotal;

            // Calculate totals in base currency
            $baseCurrencyTotal = $totalAmount * $exchangeRate;
            $baseCurrencyTax = $taxTotal * $exchangeRate;

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'po_date' => $request->po_date,
                'vendor_id' => $request->vendor_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => 'draft',
                'total_amount' => $totalAmount,
                'tax_amount' => $taxTotal,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency_total' => $baseCurrencyTotal,
                'base_currency_tax' => $baseCurrencyTax,
                'base_currency' => $baseCurrency
            ]);

            // Create PO lines
            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $lineTotal = $lineSubtotal + $lineTax;

                // Calculate base currency amounts
                $baseUnitPrice = $line['unit_price'] * $exchangeRate;
                $baseSubtotal = $lineSubtotal * $exchangeRate;
                $baseTax = $lineTax * $exchangeRate;
                $baseTotal = $lineTotal * $exchangeRate;

                $purchaseOrder->lines()->create([
                    'item_id' => $line['item_id'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'subtotal' => $lineSubtotal,
                    'tax' => $lineTax,
                    'total' => $lineTotal,
                    'base_currency_unit_price' => $baseUnitPrice,
                    'base_currency_subtotal' => $baseSubtotal,
                    'base_currency_tax' => $baseTax,
                    'base_currency_total' => $baseTotal
                ]);
            }

            // If quotation_id is provided, mark quotation as accepted
            if (isset($request->quotation_id)) {
                $quotation = VendorQuotation::findOrFail($request->quotation_id);
                $quotation->update(['status' => 'accepted']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order created successfully',
                'data' => $purchaseOrder->load(['vendor', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Purchase Order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['vendor', 'lines.item', 'lines.unitOfMeasure', 'goodsReceipts']);

        return response()->json([
            'status' => 'success',
            'data' => $purchaseOrder
        ]);
    }

    /**
     * Update the specified purchase order.
     */
    public function update(PurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        // Check if PO can be updated (only draft status)
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft Purchase Orders can be updated'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Determine currency to use
            $currencyCode = $request->currency_code ?? $purchaseOrder->currency_code;
            $baseCurrency = config('app.base_currency', 'USD');

            // Get exchange rate if the currency has changed
            $exchangeRate = $purchaseOrder->exchange_rate;

            if ($currencyCode !== $purchaseOrder->currency_code) {
                if ($currencyCode !== $baseCurrency) {
                    $rate = CurrencyRate::where('from_currency', $currencyCode)
                        ->where('to_currency', $baseCurrency)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $request->po_date)
                        ->where(function ($query) use ($request) {
                            $query->where('end_date', '>=', $request->po_date)
                                ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();

                    if (!$rate) {
                        // Try to find a reverse rate
                        $reverseRate = CurrencyRate::where('from_currency', $baseCurrency)
                            ->where('to_currency', $currencyCode)
                            ->where('is_active', true)
                            ->where('effective_date', '<=', $request->po_date)
                            ->where(function ($query) use ($request) {
                                $query->where('end_date', '>=', $request->po_date)
                                    ->orWhereNull('end_date');
                            })
                            ->orderBy('effective_date', 'desc')
                            ->first();

                        if ($reverseRate) {
                            $exchangeRate = 1 / $reverseRate->rate;
                        } else {
                            DB::rollBack();
                            return response()->json([
                                'status' => 'error',
                                'message' => 'No exchange rate found for the specified currency on the purchase date'
                            ], 422);
                        }
                    } else {
                        $exchangeRate = $rate->rate;
                    }
                } else {
                    $exchangeRate = 1.0; // Base currency
                }
            }

            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($request->lines as $line) {
                $lineSubtotal = $line['unit_price'] * $line['quantity'];
                $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }

            $totalAmount = $subtotal + $taxTotal;

            // Calculate totals in base currency
            $baseCurrencyTotal = $totalAmount * $exchangeRate;
            $baseCurrencyTax = $taxTotal * $exchangeRate;

            // Update purchase order
            $purchaseOrder->update([
                'po_date' => $request->po_date,
                'vendor_id' => $request->vendor_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'total_amount' => $totalAmount,
                'tax_amount' => $taxTotal,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency_total' => $baseCurrencyTotal,
                'base_currency_tax' => $baseCurrencyTax
            ]);

            // Update PO lines
            if ($request->has('lines')) {
                // Delete existing lines
                $purchaseOrder->lines()->delete();

                // Create new lines
                foreach ($request->lines as $line) {
                    $lineSubtotal = $line['unit_price'] * $line['quantity'];
                    $lineTax = isset($line['tax']) ? $line['tax'] : 0;
                    $lineTotal = $lineSubtotal + $lineTax;

                    // Calculate base currency amounts
                    $baseUnitPrice = $line['unit_price'] * $exchangeRate;
                    $baseSubtotal = $lineSubtotal * $exchangeRate;
                    $baseTax = $lineTax * $exchangeRate;
                    $baseTotal = $lineTotal * $exchangeRate;

                    $purchaseOrder->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $line['unit_price'],
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'subtotal' => $lineSubtotal,
                        'tax' => $lineTax,
                        'total' => $lineTotal,
                        'base_currency_unit_price' => $baseUnitPrice,
                        'base_currency_subtotal' => $baseSubtotal,
                        'base_currency_tax' => $baseTax,
                        'base_currency_total' => $baseTotal
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order updated successfully',
                'data' => $purchaseOrder->load(['vendor', 'lines.item', 'lines.unitOfMeasure'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Purchase Order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified purchase order from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        // Check if PO can be deleted (only draft status)
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft Purchase Orders can be deleted'
            ], 400);
        }

        $purchaseOrder->lines()->delete();
        $purchaseOrder->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase Order deleted successfully'
        ]);
    }

    /**
     * Update the status of the specified purchase order.
     */
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'status' => 'required|in:draft,submitted,approved,sent,partial,received,completed,canceled'
        ]);

        // Additional validations based on status transition
        $currentStatus = $purchaseOrder->status;
        $newStatus = $request->status;

        $validTransitions = [
            'draft' => ['submitted', 'canceled'],
            'submitted' => ['approved', 'canceled'],
            'approved' => ['sent', 'canceled'],
            'sent' => ['partial', 'received', 'canceled'],
            'partial' => ['completed', 'canceled'],
            'received' => ['completed', 'canceled'],
            'completed' => ['canceled'],
            'canceled' => []
        ];

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'status' => 'error',
                'message' => "Status cannot be changed from {$currentStatus} to {$newStatus}"
            ], 400);
        }

        $purchaseOrder->update(['status' => $newStatus]);

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase Order status updated successfully',
            'data' => $purchaseOrder
        ]);
    }

    /**
     * Create a new purchase order from a quotation.
     */
    public function createFromQuotation(Request $request)
    {
        $request->validate([
            'quotation_id' => 'required|exists:vendor_quotations,quotation_id'
        ]);

        $quotation = VendorQuotation::with(['lines', 'vendor', 'requestForQuotation'])
            ->findOrFail($request->quotation_id);

        // Check if quotation is in accepted status
        if ($quotation->status !== 'accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchase Order can only be created from accepted quotations'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Generate PO number
            $poNumber = $this->poNumberGenerator->generate();

            // Calculate totals
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($quotation->lines as $line) {
                $lineSubtotal = $line->unit_price * $line->quantity;
                $subtotal += $lineSubtotal;
            }

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $poNumber,
                'po_date' => now(),
                'vendor_id' => $quotation->vendor_id,
                'payment_terms' => null,
                'delivery_terms' => null,
                'expected_delivery' => null,
                'status' => 'draft',
                'total_amount' => $subtotal,
                'tax_amount' => 0,
                'currency_code' => 'USD',
                'exchange_rate' => 1.0,
                'base_currency_total' => $subtotal,
                'base_currency_tax' => 0,
                'base_currency' => config('app.base_currency', 'USD')
            ]);

            // Create PO lines from quotation lines
            foreach ($quotation->lines as $line) {
                $lineSubtotal = $line->unit_price * $line->quantity;

                $purchaseOrder->lines()->create([
                    'item_id' => $line->item_id,
                    'unit_price' => $line->unit_price,
                    'quantity' => $line->quantity,
                    'uom_id' => $line->uom_id,
                    'subtotal' => $lineSubtotal,
                    'tax' => 0,
                    'total' => $lineSubtotal,
                    'base_currency_unit_price' => $line->unit_price,
                    'base_currency_subtotal' => $lineSubtotal,
                    'base_currency_tax' => 0,
                    'base_currency_total' => $lineSubtotal
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order created from quotation successfully',
                'data' => $purchaseOrder->load(['vendor', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Purchase Order from quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert purchase order currency.
     */
    public function convertCurrency(Request $request, PurchaseOrder $purchaseOrder)
    {
        // Only allow currency conversion for draft orders
        if ($purchaseOrder->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only draft Purchase Orders can have their currency converted'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'currency_code' => 'required|string|size:3',
            'use_exchange_rate_date' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Don't convert if already in the target currency
        if ($purchaseOrder->currency_code === $request->currency_code) {
            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order is already in the specified currency',
                'data' => $purchaseOrder
            ]);
        }

        $baseCurrency = config('app.base_currency', 'USD');

        try {
            DB::beginTransaction();

            // Determine which exchange rate to use
            $useExchangeRateDate = $request->use_exchange_rate_date ?? true;
            $exchangeRateDate = $useExchangeRateDate ? $purchaseOrder->po_date : now()->format('Y-m-d');

            // Get exchange rate from base currency to target currency
            if ($request->currency_code !== $baseCurrency) {
                $rate = CurrencyRate::where('from_currency', $baseCurrency)
                    ->where('to_currency', $request->currency_code)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', $exchangeRateDate)
                    ->where(function ($query) use ($exchangeRateDate) {
                        $query->where('end_date', '>=', $exchangeRateDate)
                            ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();

                if (!$rate) {
                    // Try to find a reverse rate
                    $reverseRate = CurrencyRate::where('from_currency', $request->currency_code)
                        ->where('to_currency', $baseCurrency)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $exchangeRateDate)
                        ->where(function ($query) use ($exchangeRateDate) {
                            $query->where('end_date', '>=', $exchangeRateDate)
                                ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();

                    if ($reverseRate) {
                        $newExchangeRate = 1 / $reverseRate->rate;
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'No exchange rate found for the specified currency'
                        ], 422);
                    }
                } else {
                    $newExchangeRate = $rate->rate;
                }
            } else {
                // Converting to base currency
                $newExchangeRate = 1.0;
            }

            // Update order totals
            $newTotalAmount = $purchaseOrder->base_currency_total / $newExchangeRate;
            $newTaxAmount = $purchaseOrder->base_currency_tax / $newExchangeRate;

            // Update purchase order
            $purchaseOrder->update([
                'currency_code' => $request->currency_code,
                'exchange_rate' => $newExchangeRate,
                'total_amount' => $newTotalAmount,
                'tax_amount' => $newTaxAmount
            ]);

            // Update all line items
            foreach ($purchaseOrder->lines as $line) {
                $newUnitPrice = $line->base_currency_unit_price / $newExchangeRate;
                $newSubtotal = $line->base_currency_subtotal / $newExchangeRate;
                $newTax = $line->base_currency_tax / $newExchangeRate;
                $newTotal = $line->base_currency_total / $newExchangeRate;

                $line->update([
                    'unit_price' => $newUnitPrice,
                    'subtotal' => $newSubtotal,
                    'tax' => $newTax,
                    'total' => $newTotal
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Purchase Order currency converted successfully',
                'data' => $purchaseOrder->fresh()->load(['vendor', 'lines.item', 'lines.unitOfMeasure'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert Purchase Order currency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display outstanding quantities for a specific purchase order.
     */
    public function showOutstanding(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['lines.item', 'goodsReceipts.lines']);

        $outstandingLines = [];

        foreach ($purchaseOrder->lines as $poLine) {
            // Calculate total received quantity for this line
            $receivedQuantity = 0;

            foreach ($purchaseOrder->goodsReceipts as $receipt) {
                foreach ($receipt->lines as $receiptLine) {
                    if ($receiptLine->po_line_id === $poLine->line_id) {
                        $receivedQuantity += $receiptLine->received_quantity;
                    }
                }
            }

            // Calculate outstanding
            $outstandingQuantity = $poLine->quantity - $receivedQuantity;

            // If there's still outstanding, add to result
            if ($outstandingQuantity > 0) {
                $outstandingLines[] = [
                    'line_id' => $poLine->line_id,
                    'item_code' => $poLine->item->item_code,
                    'item_name' => $poLine->item->name,
                    'ordered_quantity' => $poLine->quantity,
                    'received_quantity' => $receivedQuantity,
                    'outstanding_quantity' => $outstandingQuantity,
                    'unit_price' => $poLine->unit_price,
                    'outstanding_value' => $outstandingQuantity * $poLine->unit_price
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'po_number' => $purchaseOrder->po_number,
                'po_date' => $purchaseOrder->po_date,
                'vendor' => $purchaseOrder->vendor->name,
                'outstanding_lines' => $outstandingLines,
                'total_outstanding_value' => array_sum(array_column($outstandingLines, 'outstanding_value'))
            ]
        ]);
    }

    /**
     * Get all purchase orders with outstanding quantities.
     */
    public function getAllOutstanding(Request $request)
    {
        $query = PurchaseOrder::with(['vendor', 'lines.item'])
            ->whereIn('status', ['sent', 'partial'])
            ->whereHas('lines', function ($q) {
                $q->whereRaw('quantity > (
                    SELECT COALESCE(SUM(grl.received_quantity), 0)
                    FROM goods_receipt_lines grl
                    JOIN goods_receipts gr ON grl.receipt_id = gr.receipt_id
                    WHERE grl.po_line_id = po_lines.line_id
                    AND gr.status = \'confirmed\'
                )');
            });

        // Apply filters
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('expected_from')) {
            $query->where('expected_delivery', '>=', $request->expected_from);
        }

        if ($request->filled('expected_to')) {
            $query->where('expected_delivery', '<=', $request->expected_to);
        }

        // Apply sorting
        $sortField = $request->input('sort_field', 'expected_delivery');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $purchaseOrders = $query->paginate($perPage);

        // Calculate outstanding for each PO
        $result = $purchaseOrders->map(function ($po) {
            $outstandingValue = 0;
            $outstandingItems = 0;

            foreach ($po->lines as $line) {
                $receivedQuantity = DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipt_lines.receipt_id', '=', 'goods_receipts.receipt_id')
                    ->where('goods_receipt_lines.po_line_id', $line->line_id)
                    ->where('goods_receipts.status', 'confirmed')
                    ->sum('goods_receipt_lines.received_quantity');

                $outstanding = $line->quantity - $receivedQuantity;

                if ($outstanding > 0) {
                    $outstandingValue += $outstanding * $line->unit_price;
                    $outstandingItems++;
                }
            }

            return [
                'po_id' => $po->po_id,
                'po_number' => $po->po_number,
                'po_date' => $po->po_date,
                'vendor_name' => $po->vendor->name,
                'expected_delivery' => $po->expected_delivery,
                'status' => $po->status,
                'total_value' => $po->total_amount,
                'outstanding_value' => $outstandingValue,
                'outstanding_percentage' => $po->total_amount > 0 ?
                    round(($outstandingValue / $po->total_amount) * 100, 2) : 0,
                'outstanding_items' => $outstandingItems
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'outstanding_pos' => $result,
                'pagination' => [
                    'total' => $purchaseOrders->total(),
                    'per_page' => $purchaseOrders->perPage(),
                    'current_page' => $purchaseOrders->currentPage(),
                    'last_page' => $purchaseOrders->lastPage()
                ]
            ]
        ]);
    }

    /**
     * Get detailed outstanding report with item details.
     */
    public function outstandingItemsReport(Request $request)
    {
        // Filter parameters
        $vendorIds = $request->input('vendor_ids', []);
        $itemIds = $request->input('item_ids', []);
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $expectedFrom = $request->input('expected_from');
        $expectedTo = $request->input('expected_to');

        // Get all POs with outstanding items
        $query = PurchaseOrder::with(['vendor', 'lines.item'])
            ->whereIn('status', ['sent', 'partial'])
            ->whereHas('lines', function ($q) {
                $q->whereRaw('quantity > (
                    SELECT COALESCE(SUM(grl.received_quantity), 0)
                    FROM goods_receipt_lines grl
                    JOIN goods_receipts gr ON grl.receipt_id = gr.receipt_id
                    WHERE grl.po_line_id = po_lines.line_id
                    AND gr.status = "confirmed"
                )');
            });

        // Apply filters
        if (!empty($vendorIds)) {
            $query->whereIn('vendor_id', $vendorIds);
        }

        if ($dateFrom) {
            $query->whereDate('po_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('po_date', '<=', $dateTo);
        }

        if ($expectedFrom) {
            $query->where('expected_delivery', '>=', $expectedFrom);
        }

        if ($expectedTo) {
            $query->where('expected_delivery', '<=', $expectedTo);
        }

        if (!empty($itemIds)) {
            $query->whereHas('lines', function ($q) use ($itemIds) {
                $q->whereIn('item_id', $itemIds);
            });
        }

        $purchaseOrders = $query->get();

        // Prepare report data
        $outstandingItems = [];

        foreach ($purchaseOrders as $po) {
            foreach ($po->lines as $line) {
                $receivedQuantity = DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipt_lines.receipt_id', '=', 'goods_receipts.receipt_id')
                    ->where('goods_receipt_lines.po_line_id', $line->line_id)
                    ->where('goods_receipts.status', 'confirmed')
                    ->sum('goods_receipt_lines.received_quantity');

                $outstandingQuantity = $line->quantity - $receivedQuantity;

                if ($outstandingQuantity > 0) {
                    $item = $line->item;

                    if (!empty($itemIds) && !in_array($item->item_id, $itemIds)) {
                        continue;
                    }

                    $itemKey = $item->item_id;

                    if (!isset($outstandingItems[$itemKey])) {
                        $outstandingItems[$itemKey] = [
                            'item_id' => $item->item_id,
                            'item_code' => $item->item_code,
                            'item_name' => $item->name,
                            'total_outstanding' => 0,
                            'total_value' => 0,
                            'orders' => []
                        ];
                    }

                    $outstandingItems[$itemKey]['total_outstanding'] += $outstandingQuantity;
                    $outstandingItems[$itemKey]['total_value'] += $outstandingQuantity * $line->unit_price;

                    $outstandingItems[$itemKey]['orders'][] = [
                        'po_id' => $po->po_id,
                        'po_number' => $po->po_number,
                        'po_date' => $po->po_date,
                        'expected_delivery' => $po->expected_delivery,
                        'vendor_name' => $po->vendor->name,
                        'ordered_quantity' => $line->quantity,
                        'received_quantity' => $receivedQuantity,
                        'outstanding_quantity' => $outstandingQuantity,
                        'unit_price' => $line->unit_price,
                        'outstanding_value' => $outstandingQuantity * $line->unit_price,
                        'days_outstanding' => now()->diffInDays($po->po_date),
                        'overdue' => $po->expected_delivery && now()->gt($po->expected_delivery)
                    ];
                }
            }
        }

        // Convert to indexed array and sort by total outstanding quantity
        $result = array_values($outstandingItems);
        usort($result, function ($a, $b) {
            return $b['total_outstanding'] <=> $a['total_outstanding'];
        });

        // Calculate overall totals
        $totalOutstanding = array_sum(array_column($result, 'total_outstanding'));
        $totalValue = array_sum(array_column($result, 'total_value'));
        $totalOrders = count(array_unique(array_merge(...array_map(function ($item) {
            return array_column($item['orders'], 'po_id');
        }, $result))));

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_outstanding_items' => count($result),
                    'total_outstanding_quantity' => $totalOutstanding,
                    'total_outstanding_value' => $totalValue,
                    'total_affected_orders' => $totalOrders
                ],
                'items' => $result
            ]
        ]);
    }

    /**
     * Download Excel template for Purchase Order import with reference data
     */
    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();

            // Create PO Header sheet
            $headerSheet = $spreadsheet->getActiveSheet();
            $headerSheet->setTitle('PO_Headers');

            $headerColumns = [
                'A1' => 'PO Number',
                'B1' => 'PO Date',
                'C1' => 'Vendor Code',
                'D1' => 'Vendor Name',
                'E1' => 'Payment Terms',
                'F1' => 'Delivery Terms',
                'G1' => 'Expected Delivery',
                'H1' => 'Currency Code',
                'I1' => 'Notes'
            ];

            foreach ($headerColumns as $cell => $value) {
                $headerSheet->setCellValue($cell, $value);
                $headerSheet->getStyle($cell)->getFont()->setBold(true);
            }

            $sampleHeaders = [
                ['PO-2024-001', '2024-01-15', 'VND001', 'ABC Supplier Ltd', 'Net 30', 'FOB Origin', '2024-02-15', 'USD', 'Sample Purchase Order'],
                ['PO-2024-002', '2024-01-16', 'VND002', 'XYZ Trading Co', 'Net 45', 'FOB Destination', '2024-02-20', 'USD', 'Office supplies order']
            ];

            $row = 2;
            foreach ($sampleHeaders as $sampleData) {
                $col = 'A';
                foreach ($sampleData as $value) {
                    $headerSheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Create PO Lines sheet
            $linesSheet = $spreadsheet->createSheet();
            $linesSheet->setTitle('PO_Lines');

            $lineColumns = [
                'A1' => 'PO Number',
                'B1' => 'Item Code',
                'C1' => 'Item Name',
                'D1' => 'Quantity',
                'E1' => 'Unit Price',
                'F1' => 'UOM Code',
                'G1' => 'UOM Name',
                'H1' => 'Tax Amount',
                'I1' => 'Line Notes'
            ];

            foreach ($lineColumns as $cell => $value) {
                $linesSheet->setCellValue($cell, $value);
                $linesSheet->getStyle($cell)->getFont()->setBold(true);
            }

            $sampleLines = [
                ['PO-2024-001', 'ITM001', 'Office Chair', '10', '150.00', 'PCS', 'Pieces', '0.00', 'Ergonomic office chairs'],
                ['PO-2024-001', 'ITM002', 'Desk Lamp', '20', '45.00', 'PCS', 'Pieces', '0.00', 'LED desk lamps'],
                ['PO-2024-002', 'ITM003', 'Printer Paper', '50', '12.50', 'PKG', 'Package', '0.00', 'A4 size, 500 sheets per package'],
                ['PO-2024-002', 'ITM004', 'Stapler', '5', '25.00', 'PCS', 'Pieces', '0.00', 'Heavy duty staplers']
            ];

            $row = 2;
            foreach ($sampleLines as $sampleData) {
                $col = 'A';
                foreach ($sampleData as $value) {
                    $linesSheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Create Reference Data Sheets (same data source as Sales Order)
            $this->createVendorsReferenceSheet($spreadsheet);
            $this->createItemsReferenceSheet($spreadsheet);
            $this->createUOMReferenceSheet($spreadsheet);
            $this->createCurrencyReferenceSheet($spreadsheet);
            $this->createPaymentTermsReferenceSheet($spreadsheet);
            $this->createDeliveryTermsReferenceSheet($spreadsheet);
            $this->createInstructionsSheet($spreadsheet);

            // Set column widths
            foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $col) {
                $headerSheet->getColumnDimension($col)->setWidth(15);
                $linesSheet->getColumnDimension($col)->setWidth(15);
            }

            $spreadsheet->setActiveSheetIndex(0);

            // Save to temporary file and return download response
            $writer = new Xlsx($spreadsheet);
            $filename = 'purchase_order_template_' . date('Y-m-d') . '.xlsx';
            $tempPath = storage_path('app/temp/' . $filename);

            if (!Storage::exists('temp')) {
                Storage::makeDirectory('temp');
            }

            $writer->save($tempPath);

            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create Vendors Reference Sheet
     */
    private function createVendorsReferenceSheet($spreadsheet)
    {
        $vendorsSheet = $spreadsheet->createSheet();
        $vendorsSheet->setTitle('Ref_Vendors');

        $headers = ['Vendor Code', 'Vendor Name', 'Email', 'Phone', 'Address', 'Preferred Currency', 'Status'];
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $vendorsSheet->setCellValue($col . '1', $header);
            $vendorsSheet->getStyle($col . '1')->getFont()->setBold(true);
            $vendorsSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $vendors = Vendor::where('is_active', true)->orderBy('vendor_code')->get();
        $row = 2;
        foreach ($vendors as $vendor) {
            $vendorsSheet->setCellValue('A' . $row, $vendor->vendor_code);
            $vendorsSheet->setCellValue('B' . $row, $vendor->name);
            $vendorsSheet->setCellValue('C' . $row, $vendor->email ?? '');
            $vendorsSheet->setCellValue('D' . $row, $vendor->phone ?? '');
            $vendorsSheet->setCellValue('E' . $row, $vendor->address ?? '');
            $vendorsSheet->setCellValue('F' . $row, $vendor->preferred_currency ?? 'USD');
            $vendorsSheet->setCellValue('G' . $row, ucfirst($vendor->status ?? 'active'));
            $row++;
        }
    }

    /**
     * Create Items Reference Sheet
     */
    private function createItemsReferenceSheet($spreadsheet)
    {
        $itemsSheet = $spreadsheet->createSheet();
        $itemsSheet->setTitle('Ref_Items');

        $headers = ['Item Code', 'Item Name', 'Category', 'Description', 'Base UOM', 'Sale Price', 'Cost Price', 'Status'];
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $itemsSheet->setCellValue($col . '1', $header);
            $itemsSheet->getStyle($col . '1')->getFont()->setBold(true);
            $itemsSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $items = Item::with(['category', 'baseUOM'])->where('is_active', true)->orderBy('item_code')->get();
        $row = 2;
        foreach ($items as $item) {
            $itemsSheet->setCellValue('A' . $row, $item->item_code);
            $itemsSheet->setCellValue('B' . $row, $item->name);
            $itemsSheet->setCellValue('C' . $row, $item->category->name ?? '');
            $itemsSheet->setCellValue('D' . $row, $item->description ?? '');
            $itemsSheet->setCellValue('E' . $row, $item->baseUOM->symbol ?? '');
            $itemsSheet->setCellValue('F' . $row, $item->unit_price ?? 0);
            $itemsSheet->setCellValue('G' . $row, $item->cost_price ?? 0);
            $itemsSheet->setCellValue('H' . $row, ucfirst($item->status ?? 'active'));
            $row++;
        }
    }

    /**
     * Create UOM Reference Sheet
     */
    private function createUOMReferenceSheet($spreadsheet)
    {
        $uomSheet = $spreadsheet->createSheet();
        $uomSheet->setTitle('Ref_UOM');

        $headers = ['UOM Code', 'UOM Name', 'UOM Type', 'Base Factor', 'Description', 'Status'];
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $uomSheet->setCellValue($col . '1', $header);
            $uomSheet->getStyle($col . '1')->getFont()->setBold(true);
            $uomSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $uoms = UnitOfMeasure::where('is_active', true)->orderBy('symbol')->get();
        $row = 2;
        foreach ($uoms as $uom) {
            $uomSheet->setCellValue('A' . $row, $uom->symbol);
            $uomSheet->setCellValue('B' . $row, $uom->name);
            $uomSheet->setCellValue('C' . $row, $uom->uom_type ?? 'Standard');
            $uomSheet->setCellValue('D' . $row, $uom->base_factor ?? 1);
            $uomSheet->setCellValue('E' . $row, $uom->description ?? '');
            $uomSheet->setCellValue('F' . $row, $uom->is_active ? 'Active' : 'Inactive');
            $row++;
        }
    }

    /**
     * Create Currency Reference Sheet
     */
    private function createCurrencyReferenceSheet($spreadsheet)
    {
        $currencySheet = $spreadsheet->createSheet();
        $currencySheet->setTitle('Ref_Currency');

        $headers = ['Currency Code', 'Currency Name', 'Symbol', 'Current Rate to Base', 'Status'];
        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $currencySheet->setCellValue($col . '1', $header);
            $currencySheet->getStyle($col . '1')->getFont()->setBold(true);
            $currencySheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Get currency data (same as Sales Order)
        if (class_exists('\App\Models\Currency')) {
            $currencies = \App\Models\Currency::where('is_active', true)->orderBy('currency_code')->get();
            $row = 2;
            foreach ($currencies as $currency) {
                $rate = CurrencyRate::getCurrentRate($currency->currency_code, config('app.base_currency', 'USD'));
                $currencySheet->setCellValue('A' . $row, $currency->currency_code);
                $currencySheet->setCellValue('B' . $row, $currency->currency_name ?? $currency->name);
                $currencySheet->setCellValue('C' . $row, $currency->symbol ?? '');
                $currencySheet->setCellValue('D' . $row, $rate ? number_format($rate, 4) : '');
                $currencySheet->setCellValue('E' . $row, $currency->is_active ? 'Active' : 'Inactive');
                $row++;
            }
        } else {
            // Fallback to common currencies
            $currencies = [
                ['USD', 'US Dollar', '$', '1.0000', 'Active'],
                ['EUR', 'Euro', '€', '', 'Active'],
                ['IDR', 'Indonesian Rupiah', 'Rp', '', 'Active'],
            ];
            $row = 2;
            foreach ($currencies as $currency) {
                $currencySheet->setCellValue('A' . $row, $currency[0]);
                $currencySheet->setCellValue('B' . $row, $currency[1]);
                $currencySheet->setCellValue('C' . $row, $currency[2]);
                $currencySheet->setCellValue('D' . $row, $currency[3]);
                $currencySheet->setCellValue('E' . $row, $currency[4]);
                $row++;
            }
        }
    }

    /**
     * Create Payment Terms Reference Sheet
     */
    private function createPaymentTermsReferenceSheet($spreadsheet)
    {
        $paymentTermsSheet = $spreadsheet->createSheet();
        $paymentTermsSheet->setTitle('Ref_PaymentTerms');

        $paymentTermsSheet->setCellValue('A1', 'Payment Terms');
        $paymentTermsSheet->setCellValue('B1', 'Description');
        $paymentTermsSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $paymentTermsSheet->getColumnDimension('A')->setAutoSize(true);
        $paymentTermsSheet->getColumnDimension('B')->setAutoSize(true);

        // Get from existing data or use defaults
        $existingTerms = collect();
        if (Schema::hasTable('purchase_orders')) {
            $poTerms = PurchaseOrder::whereNotNull('payment_terms')->distinct()->pluck('payment_terms');
            $existingTerms = $existingTerms->merge($poTerms);
        }
        if (class_exists('\App\Models\SalesOrder')) {
            $soTerms = \App\Models\SalesOrder::whereNotNull('payment_terms')->distinct()->pluck('payment_terms');
            $existingTerms = $existingTerms->merge($soTerms);
        }

        $terms = $existingTerms->unique()->filter()->sort()->values();
        if ($terms->isEmpty()) {
            $terms = collect(['Net 30', 'Net 15', 'Net 45', 'COD', 'Prepaid']);
        }

        $row = 2;
        foreach ($terms as $term) {
            $paymentTermsSheet->setCellValue('A' . $row, $term);
            $paymentTermsSheet->setCellValue('B' . $row, '');
            $row++;
        }
    }

    /**
     * Create Delivery Terms Reference Sheet
     */
    private function createDeliveryTermsReferenceSheet($spreadsheet)
    {
        $deliveryTermsSheet = $spreadsheet->createSheet();
        $deliveryTermsSheet->setTitle('Ref_DeliveryTerms');

        $deliveryTermsSheet->setCellValue('A1', 'Delivery Terms');
        $deliveryTermsSheet->setCellValue('B1', 'Description');
        $deliveryTermsSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $deliveryTermsSheet->getColumnDimension('A')->setAutoSize(true);
        $deliveryTermsSheet->getColumnDimension('B')->setAutoSize(true);

        // Get from existing data or use defaults
        $existingTerms = collect();
        if (Schema::hasTable('purchase_orders')) {
            $poTerms = PurchaseOrder::whereNotNull('delivery_terms')->distinct()->pluck('delivery_terms');
            $existingTerms = $existingTerms->merge($poTerms);
        }
        if (class_exists('\App\Models\SalesOrder')) {
            $soTerms = \App\Models\SalesOrder::whereNotNull('delivery_terms')->distinct()->pluck('delivery_terms');
            $existingTerms = $existingTerms->merge($soTerms);
        }

        $terms = $existingTerms->unique()->filter()->sort()->values();
        if ($terms->isEmpty()) {
            $terms = collect(['FOB Origin', 'FOB Destination', 'CIF', 'EXW', 'DDP']);
        }

        $row = 2;
        foreach ($terms as $term) {
            $deliveryTermsSheet->setCellValue('A' . $row, $term);
            $deliveryTermsSheet->setCellValue('B' . $row, '');
            $row++;
        }
    }

    /**
     * Create Instructions Sheet
     */
    private function createInstructionsSheet($spreadsheet)
    {
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instructions');

        $instructions = [
            'PURCHASE ORDER IMPORT INSTRUCTIONS',
            '',
            'This template contains multiple sheets for importing Purchase Orders:',
            '',
            '1. PO_Headers: Contains Purchase Order header information',
            '2. PO_Lines: Contains Purchase Order line items',
            '',
            'REFERENCE SHEETS (same data source as Sales Order):',
            '   - Ref_Vendors: Available vendors with codes and details',
            '   - Ref_Items: Available items with codes and prices',
            '   - Ref_UOM: Available units of measure',
            '   - Ref_Currency: Supported currencies with current rates',
            '   - Ref_PaymentTerms: Standard payment terms options',
            '   - Ref_DeliveryTerms: Standard delivery terms options',
            '',
            'IMPORTANT NOTES:',
            '- Use exact codes from reference sheets',
            '- Dates must be in YYYY-MM-DD format',
            '- PO Numbers in PO_Lines must match those in PO_Headers',
            '- All required fields must be filled',
            '',
            'VALIDATION OPTIONS:',
            '- Update existing: Update existing POs with same number',
            '- Validate vendors: Validate vendor codes before import',
            '- Validate items: Validate item codes before import'
        ];

        $row = 1;
        foreach ($instructions as $instruction) {
            $instructionsSheet->setCellValue('A' . $row, $instruction);
            $row++;
        }
        $instructionsSheet->getColumnDimension('A')->setWidth(80);
    }

    /**
     * Import Purchase Orders from Excel file
     */
    public function importFromExcel(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls|max:10240',
                'update_existing' => 'boolean',
                'validate_vendors' => 'boolean',
                'validate_items' => 'boolean'
            ]);

            $file = $request->file('file');
            $updateExisting = $request->get('update_existing', false);
            $validateVendors = $request->get('validate_vendors', true);
            $validateItems = $request->get('validate_items', true);

            $spreadsheet = IOFactory::load($file->getPathname());

            // Get required sheets
            $headerSheet = $spreadsheet->getSheetByName('PO_Headers');
            $linesSheet = $spreadsheet->getSheetByName('PO_Lines');

            if (!$headerSheet || !$linesSheet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Required sheets (PO_Headers, PO_Lines) not found in the uploaded file'
                ], 400);
            }

            $summary = [
                'successful' => 0,
                'failed' => 0,
                'warnings' => 0,
                'total_processed' => 0,
                'errors' => [],
                'warnings_details' => []
            ];

            $createdPOs = [];

            DB::beginTransaction();

            try {
                // Parse headers and lines
                $headers = $this->parseHeaders($headerSheet);
                $lines = $this->parseLines($linesSheet);

                // Process each PO
                foreach ($headers as $poNumber => $headerData) {
                    $summary['total_processed']++;

                    try {
                        $result = $this->processPurchaseOrder(
                            $poNumber,
                            $headerData,
                            $lines[$poNumber] ?? [],
                            $updateExisting,
                            $validateVendors,
                            $validateItems
                        );

                        if ($result['success']) {
                            $summary['successful']++;
                            $createdPOs[] = $result['po_data'];
                        } else {
                            $summary['failed']++;
                            $summary['errors'][] = "PO {$poNumber}: " . $result['message'];
                        }
                    } catch (\Exception $e) {
                        $summary['failed']++;
                        $summary['errors'][] = "PO {$poNumber}: " . $e->getMessage();
                    }
                }

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Import completed successfully',
                    'summary' => $summary,
                    'created_pos' => $createdPOs
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse headers from Excel sheet
     */
    private function parseHeaders($headerSheet)
    {
        $headers = [];
        $headerRows = $headerSheet->toArray();

        for ($row = 1; $row < count($headerRows); $row++) {
            $rowData = $headerRows[$row];
            if (empty($rowData[0])) continue;

            $headers[$rowData[0]] = [
                'po_number' => $rowData[0],
                'po_date' => $rowData[1],
                'vendor_code' => $rowData[2],
                'vendor_name' => $rowData[3] ?? '',
                'payment_terms' => $rowData[4] ?? '',
                'delivery_terms' => $rowData[5] ?? '',
                'expected_delivery' => $rowData[6] ?? null,
                'currency_code' => $rowData[7] ?? 'USD',
                'notes' => $rowData[8] ?? ''
            ];
        }

        return $headers;
    }

    /**
     * Parse lines from Excel sheet
     */
    private function parseLines($linesSheet)
    {
        $lines = [];
        $lineRows = $linesSheet->toArray();

        for ($row = 1; $row < count($lineRows); $row++) {
            $rowData = $lineRows[$row];
            if (empty($rowData[0])) continue;

            $poNumber = $rowData[0];
            if (!isset($lines[$poNumber])) {
                $lines[$poNumber] = [];
            }

            $lines[$poNumber][] = [
                'item_code' => $rowData[1],
                'item_name' => $rowData[2] ?? '',
                'quantity' => floatval($rowData[3] ?? 0),
                'unit_price' => floatval($rowData[4] ?? 0),
                'uom_code' => $rowData[5],
                'uom_name' => $rowData[6] ?? '',
                'tax' => floatval($rowData[7] ?? 0),
                'notes' => $rowData[8] ?? ''
            ];
        }

        return $lines;
    }

    /**
     * Process individual purchase order
     */
    private function processPurchaseOrder($poNumber, $headerData, $lines, $updateExisting, $validateVendors, $validateItems)
    {
        // Validate required fields
        if (empty($headerData['po_number']) || empty($headerData['po_date']) || empty($headerData['vendor_code'])) {
            return ['success' => false, 'message' => 'Required fields missing'];
        }

        // Validate vendor
        $vendor = Vendor::where('vendor_code', $headerData['vendor_code'])->first();
        if (!$vendor && $validateVendors) {
            return ['success' => false, 'message' => 'Vendor not found'];
        }
        if (!$vendor) {
            return ['success' => false, 'message' => 'Vendor not found'];
        }

        // Check existing PO
        $existingPO = PurchaseOrder::where('po_number', $poNumber)->first();
        if ($existingPO && !$updateExisting) {
            return ['success' => false, 'message' => 'PO already exists'];
        }

        if (empty($lines)) {
            return ['success' => false, 'message' => 'No lines found'];
        }

        // Validate and process lines
        $validatedLines = [];
        $totalAmount = 0;
        $totalTax = 0;

        foreach ($lines as $lineIndex => $lineData) {
            if (empty($lineData['item_code']) || $lineData['quantity'] <= 0 || empty($lineData['uom_code'])) {
                return ['success' => false, 'message' => "Invalid line data at line " . ($lineIndex + 1)];
            }

            $item = Item::where('item_code', $lineData['item_code'])->first();
            if (!$item && $validateItems) {
                return ['success' => false, 'message' => "Item {$lineData['item_code']} not found"];
            }
            if (!$item) {
                continue;
            }

            $uom = UnitOfMeasure::where('symbol', $lineData['uom_code'])->first();
            if (!$uom) {
                return ['success' => false, 'message' => "UOM {$lineData['uom_code']} not found"];
            }

            $subtotal = $lineData['quantity'] * $lineData['unit_price'];
            $tax = $lineData['tax'];
            $lineTotal = $subtotal + $tax;

            $totalAmount += $lineTotal;
            $totalTax += $tax;

            $validatedLines[] = [
                'item_id' => $item->item_id,
                'unit_price' => $lineData['unit_price'],
                'quantity' => $lineData['quantity'],
                'uom_id' => $uom->uom_id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $lineTotal,
                'base_currency_unit_price' => $lineData['unit_price'],
                'base_currency_subtotal' => $subtotal,
                'base_currency_tax' => $tax,
                'base_currency_total' => $lineTotal
            ];
        }

        if (empty($validatedLines)) {
            return ['success' => false, 'message' => 'No valid lines'];
        }

        // Create or update PO
        $poData = [
            'po_number' => $poNumber,
            'po_date' => $headerData['po_date'],
            'vendor_id' => $vendor->vendor_id,
            'payment_terms' => $headerData['payment_terms'],
            'delivery_terms' => $headerData['delivery_terms'],
            'expected_delivery' => $headerData['expected_delivery'],
            'total_amount' => $totalAmount,
            'tax_amount' => $totalTax,
            'currency_code' => $headerData['currency_code'],
            'exchange_rate' => 1.0,
            'base_currency_total' => $totalAmount,
            'base_currency_tax' => $totalTax,
            'base_currency' => config('app.base_currency', 'USD')
        ];

        if ($existingPO && $updateExisting) {
            $existingPO->update($poData);
            $existingPO->lines()->delete();
            foreach ($validatedLines as $lineData) {
                $existingPO->lines()->create($lineData);
            }
            $po = $existingPO;
        } else {
            $poData['status'] = 'draft';
            $po = PurchaseOrder::create($poData);
            foreach ($validatedLines as $lineData) {
                $po->lines()->create($lineData);
            }
        }

        return [
            'success' => true,
            'po_data' => [
                'po_id' => $po->po_id,
                'po_number' => $po->po_number,
                'vendor_name' => $vendor->name,
                'total_amount' => $totalAmount
            ]
        ];
    }

    /**
     * Export Purchase Orders to Excel
     */
    public function exportToExcel(Request $request)
    {
        try {
            $query = PurchaseOrder::with(['vendor', 'lines.item', 'lines.unitOfMeasure']);

            // Apply filters
            if ($request->filled('status')) {
                $query->whereIn('status', is_array($request->status) ? $request->status : [$request->status]);
            }
            if ($request->filled('vendor_id')) {
                $query->where('vendor_id', $request->vendor_id);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('po_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('po_date', '<=', $request->date_to);
            }
            if ($request->filled('po_numbers')) {
                $query->whereIn('po_number', $request->po_numbers);
            }

            $purchaseOrders = $query->orderBy('po_date', 'desc')->get();

            if ($purchaseOrders->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No purchase orders found for export'
                ], 404);
            }

            $spreadsheet = new Spreadsheet();

            // Create export sheets
            $this->createExportHeadersSheet($spreadsheet, $purchaseOrders);
            $this->createExportLinesSheet($spreadsheet, $purchaseOrders);
            $this->createExportSummarySheet($spreadsheet, $purchaseOrders);

            $spreadsheet->setActiveSheetIndex(0);

            // Save and return download
            $writer = new Xlsx($spreadsheet);
            $filename = 'purchase_orders_export_' . date('Y-m-d_H-i-s') . '.xlsx';
            $tempPath = storage_path('app/temp/' . $filename);

            if (!Storage::exists('temp')) {
                Storage::makeDirectory('temp');
            }

            $writer->save($tempPath);

            return response()->download($tempPath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export Purchase Orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create export headers sheet
     */
    private function createExportHeadersSheet($spreadsheet, $purchaseOrders)
    {
        $headerSheet = $spreadsheet->getActiveSheet();
        $headerSheet->setTitle('PO_Headers');

        $headerColumns = [
            'A1' => 'PO Number',
            'B1' => 'PO Date',
            'C1' => 'Vendor Code',
            'D1' => 'Vendor Name',
            'E1' => 'Payment Terms',
            'F1' => 'Delivery Terms',
            'G1' => 'Expected Delivery',
            'H1' => 'Status',
            'I1' => 'Currency Code',
            'J1' => 'Total Amount',
            'K1' => 'Tax Amount',
            'L1' => 'Created Date'
        ];

        foreach ($headerColumns as $cell => $value) {
            $headerSheet->setCellValue($cell, $value);
            $headerSheet->getStyle($cell)->getFont()->setBold(true);
            $headerSheet->getColumnDimension(substr($cell, 0, 1))->setAutoSize(true);
        }

        $row = 2;
        foreach ($purchaseOrders as $po) {
            $headerSheet->setCellValue('A' . $row, $po->po_number);
            $headerSheet->setCellValue('B' . $row, $po->po_date->format('Y-m-d'));
            $headerSheet->setCellValue('C' . $row, $po->vendor->vendor_code ?? '');
            $headerSheet->setCellValue('D' . $row, $po->vendor->name ?? '');
            $headerSheet->setCellValue('E' . $row, $po->payment_terms ?? '');
            $headerSheet->setCellValue('F' . $row, $po->delivery_terms ?? '');
            $headerSheet->setCellValue('G' . $row, $po->expected_delivery ? $po->expected_delivery->format('Y-m-d') : '');
            $headerSheet->setCellValue('H' . $row, ucfirst($po->status));
            $headerSheet->setCellValue('I' . $row, $po->currency_code ?? 'USD');
            $headerSheet->setCellValue('J' . $row, $po->total_amount);
            $headerSheet->setCellValue('K' . $row, $po->tax_amount);
            $headerSheet->setCellValue('L' . $row, $po->created_at->format('Y-m-d H:i:s'));
            $row++;
        }
    }

    /**
     * Create export lines sheet
     */
    private function createExportLinesSheet($spreadsheet, $purchaseOrders)
    {
        $linesSheet = $spreadsheet->createSheet();
        $linesSheet->setTitle('PO_Lines');

        $lineColumns = [
            'A1' => 'PO Number',
            'B1' => 'Item Code',
            'C1' => 'Item Name',
            'D1' => 'Quantity',
            'E1' => 'Unit Price',
            'F1' => 'UOM Code',
            'G1' => 'UOM Name',
            'H1' => 'Subtotal',
            'I1' => 'Tax Amount',
            'J1' => 'Total',
            'K1' => 'Currency',
            'L1' => 'Base Currency Total'
        ];

        foreach ($lineColumns as $cell => $value) {
            $linesSheet->setCellValue($cell, $value);
            $linesSheet->getStyle($cell)->getFont()->setBold(true);
            $linesSheet->getColumnDimension(substr($cell, 0, 1))->setAutoSize(true);
        }

        $row = 2;
        foreach ($purchaseOrders as $po) {
            foreach ($po->lines as $line) {
                $linesSheet->setCellValue('A' . $row, $po->po_number);
                $linesSheet->setCellValue('B' . $row, $line->item->item_code ?? '');
                $linesSheet->setCellValue('C' . $row, $line->item->name ?? '');
                $linesSheet->setCellValue('D' . $row, $line->quantity);
                $linesSheet->setCellValue('E' . $row, $line->unit_price);
                $linesSheet->setCellValue('F' . $row, $line->unitOfMeasure->symbol ?? '');
                $linesSheet->setCellValue('G' . $row, $line->unitOfMeasure->name ?? '');
                $linesSheet->setCellValue('H' . $row, $line->subtotal);
                $linesSheet->setCellValue('I' . $row, $line->tax);
                $linesSheet->setCellValue('J' . $row, $line->total);
                $linesSheet->setCellValue('K' . $row, $po->currency_code ?? 'USD');
                $linesSheet->setCellValue('L' . $row, $line->base_currency_total ?? $line->total);
                $row++;
            }
        }
    }

    /**
     * Create export summary sheet
     */
    private function createExportSummarySheet($spreadsheet, $purchaseOrders)
    {
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');

        $summaryData = [
            ['Export Summary', ''],
            ['Generated On', now()->format('Y-m-d H:i:s')],
            ['Total Purchase Orders', $purchaseOrders->count()],
            ['Total Lines', $purchaseOrders->sum(function ($po) {
                return $po->lines->count();
            })],
            ['Total Value (Base Currency)', $purchaseOrders->sum('base_currency_total')],
            [''],
            ['Status Breakdown', ''],
        ];

        $statusCounts = $purchaseOrders->groupBy('status')->map(function ($group) {
            return $group->count();
        });
        foreach ($statusCounts as $status => $count) {
            $summaryData[] = [ucfirst($status), $count];
        }

        $row = 1;
        foreach ($summaryData as $data) {
            if (!empty($data[0])) {
                $summarySheet->setCellValue('A' . $row, $data[0]);
                if (isset($data[1])) {
                    $summarySheet->setCellValue('B' . $row, $data[1]);
                }
                if (empty($data[1]) || $data[0] === 'Export Summary' || $data[0] === 'Status Breakdown') {
                    $summarySheet->getStyle('A' . $row)->getFont()->setBold(true);
                }
            }
            $row++;
        }

        $summarySheet->getColumnDimension('A')->setWidth(25);
        $summarySheet->getColumnDimension('B')->setWidth(20);
    }
}

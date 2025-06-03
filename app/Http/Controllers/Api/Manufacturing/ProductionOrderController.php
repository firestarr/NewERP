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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                    // New multicurrency fields
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
                        // New multicurrency fields
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
                'tax_amount' => 0
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
                    'total' => $lineSubtotal
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

            // Calculate conversion factor between old and new currencies
            $conversionFactor = $newExchangeRate / $purchaseOrder->exchange_rate;

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
            // Hitung total yang sudah diterima untuk line ini
            $receivedQuantity = 0;

            foreach ($purchaseOrder->goodsReceipts as $receipt) {
                foreach ($receipt->lines as $receiptLine) {
                    if ($receiptLine->po_line_id === $poLine->line_id) {
                        $receivedQuantity += $receiptLine->received_quantity;
                    }
                }
            }

            // Hitung outstanding
            $outstandingQuantity = $poLine->quantity - $receivedQuantity;

            // Jika masih ada outstanding, tambahkan ke hasil
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
            ->whereIn('status', ['sent', 'partial']) // Hanya PO yang relevan
            ->whereHas('lines', function ($q) {
                $q->whereRaw('quantity > (
                    SELECT COALESCE(SUM(grl.received_quantity), 0)
                    FROM goods_receipt_lines grl
                    JOIN goods_receipts gr ON grl.receipt_id = gr.receipt_id
                    WHERE grl.po_line_id = po_lines.line_id
                    AND gr.status = \'confirmed\'
                )');
            });

        // Filter tambahan
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('expected_from')) {
            $query->where('expected_delivery', '>=', $request->expected_from);
        }

        if ($request->filled('expected_to')) {
            $query->where('expected_delivery', '<=', $request->expected_to);
        }

        // Sorting
        $sortField = $request->input('sort_field', 'expected_delivery');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $purchaseOrders = $query->paginate($perPage);

        // Hitung outstanding untuk setiap PO
        $result = $purchaseOrders->map(function ($po) {
            $outstandingValue = 0;
            $outstandingItems = 0;

            foreach ($po->lines as $line) {
                // Hitung received quantity
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
            ->whereIn('status', ['sent', 'partial']) // Hanya PO yang relevan
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

        // Filter by specific items
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
                // Calculate received quantity
                $receivedQuantity = DB::table('goods_receipt_lines')
                    ->join('goods_receipts', 'goods_receipt_lines.receipt_id', '=', 'goods_receipts.receipt_id')
                    ->where('goods_receipt_lines.po_line_id', $line->line_id)
                    ->where('goods_receipts.status', 'confirmed')
                    ->sum('goods_receipt_lines.received_quantity');

                $outstandingQuantity = $line->quantity - $receivedQuantity;

                // Only include if outstanding
                if ($outstandingQuantity > 0) {
                    $item = $line->item;

                    // Skip if filtering by item and not in the list
                    if (!empty($itemIds) && !in_array($item->item_id, $itemIds)) {
                        continue;
                    }

                    // Create item key for grouping
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

                    // Add to total outstanding for this item
                    $outstandingItems[$itemKey]['total_outstanding'] += $outstandingQuantity;
                    $outstandingItems[$itemKey]['total_value'] += $outstandingQuantity * $line->unit_price;

                    // Add order details
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
     * Download Excel template for Purchase Order import (Single Sheet Format like SO)
     */
    public function downloadTemplate()
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Purchase_Orders');

            // Header columns (single sheet format like SO)
            $columns = [
                'A1' => 'PO Number',
                'B1' => 'PO Date',
                'C1' => 'Vendor Code',
                'D1' => 'Vendor Name',
                'E1' => 'Payment Terms',
                'F1' => 'Delivery Terms',
                'G1' => 'Expected Delivery',
                'H1' => 'Currency Code',
                'I1' => 'Item Code',
                'J1' => 'Item Name',
                'K1' => 'Quantity',
                'L1' => 'Unit Price',
                'M1' => 'UOM Code',
                'N1' => 'UOM Name',
                'O1' => 'Tax Amount',
                'P1' => 'Notes'
            ];

            // Set headers
            foreach ($columns as $cell => $value) {
                $sheet->setCellValue($cell, $value);
                $sheet->getStyle($cell)->getFont()->setBold(true);
            }

            // Add sample data
            $sampleData = [
                ['PO-2024-001', '2024-01-15', 'VND001', 'ABC Supplier Ltd', 'Net 30', 'FOB Origin', '2024-02-15', 'USD', 'ITM001', 'Office Chair', '10', '150.00', 'PCS', 'Pieces', '0.00', 'Ergonomic office chairs'],
                ['PO-2024-001', '2024-01-15', 'VND001', 'ABC Supplier Ltd', 'Net 30', 'FOB Origin', '2024-02-15', 'USD', 'ITM002', 'Desk Lamp', '20', '45.00', 'PCS', 'Pieces', '0.00', 'LED desk lamps'],
                ['PO-2024-002', '2024-01-16', 'VND002', 'XYZ Trading Co', 'Net 45', 'FOB Destination', '2024-02-20', 'USD', 'ITM003', 'Printer Paper', '50', '12.50', 'PKG', 'Package', '0.00', 'A4 size, 500 sheets per package'],
                ['PO-2024-002', '2024-01-16', 'VND002', 'XYZ Trading Co', 'Net 45', 'FOB Destination', '2024-02-20', 'USD', 'ITM004', 'Stapler', '5', '25.00', 'PCS', 'Pieces', '0.00', 'Heavy duty staplers']
            ];

            $row = 2;
            foreach ($sampleData as $data) {
                $col = 'A';
                foreach ($data as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }

            // Create Instructions sheet
            $instructionsSheet = $spreadsheet->createSheet();
            $instructionsSheet->setTitle('Instructions');

            $instructions = [
                'PURCHASE ORDER IMPORT INSTRUCTIONS',
                '',
                'This template uses a single sheet format for importing Purchase Orders:',
                '',
                'COLUMN DESCRIPTIONS:',
                '- PO Number: Unique identifier for the purchase order',
                '- PO Date: Date when the PO was created (YYYY-MM-DD format)',
                '- Vendor Code: Code identifying the vendor (must exist in system)',
                '- Vendor Name: Name of the vendor (for reference only)',
                '- Payment Terms: Payment terms (e.g., Net 30, Net 45)',
                '- Delivery Terms: Delivery terms (e.g., FOB Origin)',
                '- Expected Delivery: Expected delivery date (YYYY-MM-DD format)',
                '- Currency Code: Currency code (e.g., USD, EUR, IDR)',
                '- Item Code: Code identifying the item to purchase (must exist in system)',
                '- Item Name: Name of the item (for reference only)',
                '- Quantity: Quantity to purchase (must be > 0)',
                '- Unit Price: Price per unit (must be >= 0)',
                '- UOM Code: Unit of measure code (must exist in system)',
                '- UOM Name: Unit of measure name (for reference only)',
                '- Tax Amount: Tax amount per line item',
                '- Notes: Additional notes for this line item',
                '',
                'IMPORTANT NOTES:',
                '- Multiple lines for the same PO Number will be grouped together',
                '- Header information (PO Number, Date, Vendor, etc.) should be the same for all lines of the same PO',
                '- Required fields: PO Number, PO Date, Vendor Code, Item Code, Quantity, Unit Price, UOM Code',
                '- Vendor codes, Item codes, and UOM codes must exist in the system',
                '- Dates must be in YYYY-MM-DD format',
                '- Numeric values must be valid numbers',
                '',
                'VALIDATION OPTIONS:',
                '- Update existing: If checked, existing POs with same number will be updated',
                '- Validate vendors: If checked, vendor codes will be validated before import',
                '- Validate items: If checked, item codes will be validated before import',
                '',
                'REFERENCE DATA:',
                'Use the Reference Data sheets to check valid codes:',
                '- Vendors: Check vendor_code column',
                '- Items: Check item_code column',
                '- UOMs: Check symbol or name column'
            ];

            $row = 1;
            foreach ($instructions as $instruction) {
                $instructionsSheet->setCellValue('A' . $row, $instruction);
                $row++;
            }

            // Create Reference Data sheets
            $this->createReferenceDataSheets($spreadsheet);

            // Set column widths
            foreach (range('A', 'P') as $col) {
                $sheet->getColumnDimension($col)->setWidth(15);
            }
            $instructionsSheet->getColumnDimension('A')->setWidth(80);

            // Set active sheet to main sheet
            $spreadsheet->setActiveSheetIndex(0);

            $filename = 'purchase_order_template_' . date('Y-m-d') . '.xlsx';

            // Use StreamedResponse for proper CORS handling
            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create reference data sheets for validation
     */
    private function createReferenceDataSheets(Spreadsheet $spreadsheet)
    {
        // Vendors sheet
        $vendorsSheet = $spreadsheet->createSheet();
        $vendorsSheet->setTitle('Vendors');
        $vendorsSheet->setCellValue('A1', 'vendor_code');
        $vendorsSheet->setCellValue('B1', 'name');
        $vendorsSheet->setCellValue('C1', 'email');
        $vendorsSheet->setCellValue('D1', 'phone');

        $vendors = Vendor::select('vendor_code', 'name', 'email', 'phone')->get();
        $row = 2;
        foreach ($vendors as $vendor) {
            $vendorsSheet->setCellValue('A' . $row, $vendor->vendor_code);
            $vendorsSheet->setCellValue('B' . $row, $vendor->name);
            $vendorsSheet->setCellValue('C' . $row, $vendor->email);
            $vendorsSheet->setCellValue('D' . $row, $vendor->phone);
            $row++;
        }

        // Items sheet
        $itemsSheet = $spreadsheet->createSheet();
        $itemsSheet->setTitle('Items');
        $itemsSheet->setCellValue('A1', 'item_code');
        $itemsSheet->setCellValue('B1', 'name');
        $itemsSheet->setCellValue('C1', 'description');
        $itemsSheet->setCellValue('D1', 'category');

        $items = Item::select('item_code', 'name', 'description', 'category')->get();
        $row = 2;
        foreach ($items as $item) {
            $itemsSheet->setCellValue('A' . $row, $item->item_code);
            $itemsSheet->setCellValue('B' . $row, $item->name);
            $itemsSheet->setCellValue('C' . $row, $item->description);
            $itemsSheet->setCellValue('D' . $row, $item->category);
            $row++;
        }

        // UOMs sheet
        $uomsSheet = $spreadsheet->createSheet();
        $uomsSheet->setTitle('UOMs');
        $uomsSheet->setCellValue('A1', 'symbol');
        $uomsSheet->setCellValue('B1', 'name');
        $uomsSheet->setCellValue('C1', 'description');

        $uoms = UnitOfMeasure::select('symbol', 'name', 'description')->get();
        $row = 2;
        foreach ($uoms as $uom) {
            $uomsSheet->setCellValue('A' . $row, $uom->symbol);
            $uomsSheet->setCellValue('B' . $row, $uom->name);
            $uomsSheet->setCellValue('C' . $row, $uom->description);
            $row++;
        }

        // Auto-size columns for reference sheets
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $vendorsSheet->getColumnDimension($col)->setAutoSize(true);
            $itemsSheet->getColumnDimension($col)->setAutoSize(true);
            $uomsSheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Import Purchase Orders from Excel file (Single Sheet Format like SO)
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
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $summary = [
                'successful' => 0,
                'failed' => 0,
                'warnings' => 0,
                'total_processed' => 0,
                'errors' => [],
                'warnings_details' => []
            ];

            $createdPOs = [];
            $groupedData = [];

            DB::beginTransaction();

            try {
                // Group data by PO Number (skip header row)
                for ($row = 1; $row < count($data); $row++) {
                    $rowData = $data[$row];

                    if (empty($rowData[0])) continue; // Skip empty rows

                    $poNumber = $rowData[0];

                    if (!isset($groupedData[$poNumber])) {
                        $groupedData[$poNumber] = [
                            'header' => [
                                'po_number' => $rowData[0],
                                'po_date' => $rowData[1],
                                'vendor_code' => $rowData[2],
                                'vendor_name' => $rowData[3] ?? '',
                                'payment_terms' => $rowData[4] ?? '',
                                'delivery_terms' => $rowData[5] ?? '',
                                'expected_delivery' => $rowData[6] ?? null,
                                'currency_code' => $rowData[7] ?? 'USD'
                            ],
                            'lines' => []
                        ];
                    }

                    $groupedData[$poNumber]['lines'][] = [
                        'item_code' => $rowData[8],
                        'item_name' => $rowData[9] ?? '',
                        'quantity' => floatval($rowData[10] ?? 0),
                        'unit_price' => floatval($rowData[11] ?? 0),
                        'uom_code' => $rowData[12],
                        'uom_name' => $rowData[13] ?? '',
                        'tax' => floatval($rowData[14] ?? 0),
                        'notes' => $rowData[15] ?? ''
                    ];
                }

                // Process each PO
                foreach ($groupedData as $poNumber => $poData) {
                    $summary['total_processed']++;

                    try {
                        $headerData = $poData['header'];
                        $linesData = $poData['lines'];

                        // Validate required fields
                        if (empty($headerData['po_number'])) {
                            throw new \Exception("PO Number is required");
                        }

                        if (empty($headerData['po_date'])) {
                            throw new \Exception("PO Date is required");
                        }

                        if (empty($headerData['vendor_code'])) {
                            throw new \Exception("Vendor Code is required");
                        }

                        // Validate dates
                        try {
                            $poDate = new \DateTime($headerData['po_date']);
                            $expectedDelivery = null;
                            if (!empty($headerData['expected_delivery'])) {
                                $expectedDelivery = new \DateTime($headerData['expected_delivery']);
                            }
                        } catch (\Exception $e) {
                            throw new \Exception("Invalid date format. Use YYYY-MM-DD format");
                        }

                        // Check if PO already exists
                        $existingPO = PurchaseOrder::where('po_number', $poNumber)->first();
                        if ($existingPO && !$updateExisting) {
                            throw new \Exception("PO Number {$poNumber} already exists");
                        }

                        // Validate vendor
                        $vendor = null;
                        if ($validateVendors) {
                            $vendor = Vendor::where('vendor_code', $headerData['vendor_code'])->first();
                            if (!$vendor) {
                                throw new \Exception("Vendor with code {$headerData['vendor_code']} not found");
                            }
                        } else {
                            $vendor = Vendor::where('vendor_code', $headerData['vendor_code'])->first();
                            if (!$vendor) {
                                $summary['warnings']++;
                                $summary['warnings_details'][] = "Vendor with code {$headerData['vendor_code']} not found for PO {$poNumber}";
                                continue;
                            }
                        }

                        // Validate lines
                        if (empty($linesData)) {
                            throw new \Exception("No lines found for PO {$poNumber}");
                        }

                        $validatedLines = [];
                        $totalAmount = 0;
                        $totalTax = 0;

                        foreach ($linesData as $lineIndex => $lineData) {
                            try {
                                // Validate required fields
                                if (empty($lineData['item_code'])) {
                                    throw new \Exception("Item Code is required for line " . ($lineIndex + 1));
                                }

                                if ($lineData['quantity'] <= 0) {
                                    throw new \Exception("Quantity must be greater than 0 for line " . ($lineIndex + 1));
                                }

                                if ($lineData['unit_price'] < 0) {
                                    throw new \Exception("Unit Price cannot be negative for line " . ($lineIndex + 1));
                                }

                                if (empty($lineData['uom_code'])) {
                                    throw new \Exception("UOM Code is required for line " . ($lineIndex + 1));
                                }

                                // Validate item
                                $item = null;
                                if ($validateItems) {
                                    $item = Item::where('item_code', $lineData['item_code'])->first();
                                    if (!$item) {
                                        throw new \Exception("Item with code {$lineData['item_code']} not found for line " . ($lineIndex + 1));
                                    }
                                } else {
                                    $item = Item::where('item_code', $lineData['item_code'])->first();
                                    if (!$item) {
                                        $summary['warnings']++;
                                        $summary['warnings_details'][] = "Item with code {$lineData['item_code']} not found for PO {$poNumber} line " . ($lineIndex + 1);
                                        continue;
                                    }
                                }

                                // Validate UOM
                                $uom = UnitOfMeasure::where('symbol', $lineData['uom_code'])
                                    ->orWhere('name', $lineData['uom_code'])
                                    ->first();
                                if (!$uom) {
                                    throw new \Exception("UOM with code {$lineData['uom_code']} not found for line " . ($lineIndex + 1));
                                }

                                // Calculate line totals
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
                            } catch (\Exception $e) {
                                throw new \Exception("Line validation error: " . $e->getMessage());
                            }
                        }

                        if (empty($validatedLines)) {
                            throw new \Exception("No valid lines found for PO {$poNumber}");
                        }

                        // Create or update PO
                        if ($existingPO && $updateExisting) {
                            $existingPO->update([
                                'po_date' => $poDate->format('Y-m-d'),
                                'vendor_id' => $vendor->vendor_id,
                                'payment_terms' => $headerData['payment_terms'],
                                'delivery_terms' => $headerData['delivery_terms'],
                                'expected_delivery' => $expectedDelivery ? $expectedDelivery->format('Y-m-d') : null,
                                'total_amount' => $totalAmount,
                                'tax_amount' => $totalTax,
                                'currency_code' => $headerData['currency_code'],
                                'exchange_rate' => 1.0,
                                'base_currency_total' => $totalAmount,
                                'base_currency_tax' => $totalTax,
                                'base_currency' => config('app.base_currency', 'USD')
                            ]);

                            $existingPO->lines()->delete();
                            foreach ($validatedLines as $lineData) {
                                $existingPO->lines()->create($lineData);
                            }

                            $createdPOs[] = [
                                'po_id' => $existingPO->po_id,
                                'po_number' => $existingPO->po_number,
                                'vendor_name' => $vendor->name,
                                'total_amount' => $totalAmount
                            ];
                        } else {
                            $newPO = PurchaseOrder::create([
                                'po_number' => $poNumber,
                                'po_date' => $poDate->format('Y-m-d'),
                                'vendor_id' => $vendor->vendor_id,
                                'payment_terms' => $headerData['payment_terms'],
                                'delivery_terms' => $headerData['delivery_terms'],
                                'expected_delivery' => $expectedDelivery ? $expectedDelivery->format('Y-m-d') : null,
                                'status' => 'draft',
                                'total_amount' => $totalAmount,
                                'tax_amount' => $totalTax,
                                'currency_code' => $headerData['currency_code'],
                                'exchange_rate' => 1.0,
                                'base_currency_total' => $totalAmount,
                                'base_currency_tax' => $totalTax,
                                'base_currency' => config('app.base_currency', 'USD')
                            ]);

                            foreach ($validatedLines as $lineData) {
                                $newPO->lines()->create($lineData);
                            }

                            $createdPOs[] = [
                                'po_id' => $newPO->po_id,
                                'po_number' => $newPO->po_number,
                                'vendor_name' => $vendor->name,
                                'total_amount' => $totalAmount
                            ];
                        }

                        $summary['successful']++;
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
     * Export Purchase Orders to Excel (Single Sheet Format like SO)
     */
    public function exportToExcel(Request $request)
    {
        try {
            $query = PurchaseOrder::with(['vendor', 'lines.item', 'lines.unitOfMeasure']);

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
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Purchase_Orders');

            // Header columns
            $columns = [
                'A1' => 'PO Number',
                'B1' => 'PO Date',
                'C1' => 'Vendor Code',
                'D1' => 'Vendor Name',
                'E1' => 'Payment Terms',
                'F1' => 'Delivery Terms',
                'G1' => 'Expected Delivery',
                'H1' => 'Status',
                'I1' => 'Currency Code',
                'J1' => 'Item Code',
                'K1' => 'Item Name',
                'L1' => 'Quantity',
                'M1' => 'Unit Price',
                'N1' => 'UOM Code',
                'O1' => 'UOM Name',
                'P1' => 'Subtotal',
                'Q1' => 'Tax Amount',
                'R1' => 'Total',
                'S1' => 'Base Currency Total',
                'T1' => 'Created Date'
            ];

            // Set headers
            foreach ($columns as $cell => $value) {
                $sheet->setCellValue($cell, $value);
                $sheet->getStyle($cell)->getFont()->setBold(true);
            }

            // Fill data
            $row = 2;
            foreach ($purchaseOrders as $po) {
                foreach ($po->lines as $line) {
                    $sheet->setCellValue('A' . $row, $po->po_number);
                    $sheet->setCellValue('B' . $row, $po->po_date->format('Y-m-d'));
                    $sheet->setCellValue('C' . $row, $po->vendor->vendor_code ?? '');
                    $sheet->setCellValue('D' . $row, $po->vendor->name ?? '');
                    $sheet->setCellValue('E' . $row, $po->payment_terms ?? '');
                    $sheet->setCellValue('F' . $row, $po->delivery_terms ?? '');
                    $sheet->setCellValue('G' . $row, $po->expected_delivery ? $po->expected_delivery->format('Y-m-d') : '');
                    $sheet->setCellValue('H' . $row, ucfirst($po->status));
                    $sheet->setCellValue('I' . $row, $po->currency_code ?? 'USD');
                    $sheet->setCellValue('J' . $row, $line->item->item_code ?? '');
                    $sheet->setCellValue('K' . $row, $line->item->name ?? '');
                    $sheet->setCellValue('L' . $row, $line->quantity);
                    $sheet->setCellValue('M' . $row, $line->unit_price);
                    $sheet->setCellValue('N' . $row, $line->unitOfMeasure->symbol ?? '');
                    $sheet->setCellValue('O' . $row, $line->unitOfMeasure->name ?? '');
                    $sheet->setCellValue('P' . $row, $line->subtotal);
                    $sheet->setCellValue('Q' . $row, $line->tax);
                    $sheet->setCellValue('R' . $row, $line->total);
                    $sheet->setCellValue('S' . $row, $line->base_currency_total ?? $line->total);
                    $sheet->setCellValue('T' . $row, $po->created_at->format('Y-m-d H:i:s'));
                    $row++;
                }
            }

            // Set column widths
            foreach (range('A', 'T') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'purchase_orders_export_' . date('Y-m-d_H-i-s') . '.xlsx';

            return new StreamedResponse(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment;filename="' . $filename . '"',
                'Cache-Control' => 'max-age=0',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export Purchase Orders: ' . $e->getMessage()
            ], 500);
        }
    }
}

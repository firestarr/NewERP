<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\SOLine;
use App\Models\Sales\SalesQuotation;
use App\Models\Sales\SalesQuotationLine;
use App\Models\Sales\DeliveryLine;
use App\Models\Sales\Customer;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Models\CurrencyRate;
use App\Models\ItemStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

class SalesOrderController extends Controller
{
    /**
     * Display a listing of the sales orders.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'salesQuotation', 'deliveries', 'salesInvoices']);

        // Filters
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Date range filter
        if ($request->has('date_range') && $request->date_range !== 'all') {
            $dateRange = $request->date_range;
            $today = date('Y-m-d');

            switch ($dateRange) {
                case 'today':
                    $query->whereDate('so_date', $today);
                    break;
                case 'week':
                    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
                    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
                    $query->whereBetween('so_date', [$startOfWeek, $endOfWeek]);
                    break;
                case 'month':
                    $startOfMonth = date('Y-m-01');
                    $endOfMonth = date('Y-m-t');
                    $query->whereBetween('so_date', [$startOfMonth, $endOfMonth]);
                    break;
            }
        }

        // Custom date range filter
        if ($request->has('start_date') && $request->has('end_date') && $request->start_date !== '' && $request->end_date !== '') {
            $query->whereBetween('so_date', [$request->start_date, $request->end_date]);
        }

        // Custom date range filters for Excel export
        if ($request->has('dateFrom') && $request->dateFrom !== '') {
            $query->where('so_date', '>=', $request->dateFrom);
        }

        if ($request->has('dateTo') && $request->dateTo !== '') {
            $query->where('so_date', '<=', $request->dateTo);
        }

        if ($request->has('customer_id') && $request->customer_id !== '') {
            $query->where('customer_id', $request->customer_id);
        }

        // Sorting
        $sortField = $request->get('sort_field', 'so_id');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = 10;
        if ($request->has('per_page') && is_numeric($request->per_page)) {
            $perPage = (int) $request->per_page;
        }

        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => $orders->items(),
            'meta' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
        ], 200);
    }

    /**
     * Store a newly created sales order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'so_number' => 'required|unique:SalesOrder,so_number',
            'so_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'quotation_id' => 'nullable|exists:SalesQuotation,quotation_id',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'expected_delivery' => 'nullable|date',
            'status' => 'required|string|max:50',
            'currency_code' => 'nullable|string|size:3',
            'lines' => 'required|array',
            'lines.*.item_id' => 'required|exists:items,item_id',
            'lines.*.unit_price' => 'nullable|numeric|min:0',
            'lines.*.quantity' => 'required|numeric|min:0',
            'lines.*.uom_id' => 'required|exists:unit_of_measures,uom_id',
            'lines.*.discount' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the customer to check for preferred currency
            $customer = Customer::find($request->customer_id);

            // Determine currency to use (from request, customer preference, or system default)
            $currencyCode = $request->currency_code ?? $customer->preferred_currency ?? config('app.base_currency', 'USD');
            $baseCurrency = config('app.base_currency', 'USD');

            // Get exchange rate
            $exchangeRate = 1.0; // Default for base currency

            if ($currencyCode !== $baseCurrency) {
                $rate = CurrencyRate::where('from_currency', $currencyCode)
                    ->where('to_currency', $baseCurrency)
                    ->where('is_active', true)
                    ->where('effective_date', '<=', $request->so_date)
                    ->where(function ($query) use ($request) {
                        $query->where('end_date', '>=', $request->so_date)
                            ->orWhereNull('end_date');
                    })
                    ->orderBy('effective_date', 'desc')
                    ->first();

                if (!$rate) {
                    // Try to find a reverse rate
                    $reverseRate = CurrencyRate::where('from_currency', $baseCurrency)
                        ->where('to_currency', $currencyCode)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $request->so_date)
                        ->where(function ($query) use ($request) {
                            $query->where('end_date', '>=', $request->so_date)
                                ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();

                    if ($reverseRate) {
                        $exchangeRate = 1 / $reverseRate->rate;
                    } else {
                        DB::rollBack();
                        return response()->json([
                            'message' => 'No exchange rate found for the specified currency on the sales date'
                        ], 422);
                    }
                } else {
                    $exchangeRate = $rate->rate;
                }
            }

            $totalAmount = 0;
            $taxAmount = 0;

            // Create sales order
            $salesOrder = SalesOrder::create([
                'so_number' => $request->so_number,
                'so_date' => $request->so_date,
                'customer_id' => $request->customer_id,
                'quotation_id' => $request->quotation_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => $request->status,
                'total_amount' => 0, // Will be updated later
                'tax_amount' => 0,    // Will be updated later
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'base_currency' => $baseCurrency,
                'base_currency_total' => 0, // Will be updated later
                'base_currency_tax' => 0    // Will be updated later
            ]);

            // Create sales order lines
            foreach ($request->lines as $line) {
                // Get the item
                $item = Item::find($line['item_id']);

                // Check if the item is sellable
                if (!$item->is_sellable) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Item ' . $item->name . ' is not sellable'
                    ], 422);
                }

                // If unit_price is not provided, get the best sale price for this customer and quantity in order currency
                $unitPrice = $line['unit_price'] ?? $item->getBestSalePriceInCurrency($request->customer_id, $line['quantity'], $currencyCode);

                $subtotal = $unitPrice * $line['quantity'];
                $discount = isset($line['discount']) ? $line['discount'] : 0;
                $tax = isset($line['tax']) ? $line['tax'] : 0;
                $total = $subtotal - $discount + $tax;

                // Calculate base currency values
                $baseUnitPrice = $unitPrice * $exchangeRate;
                $baseSubtotal = $subtotal * $exchangeRate;
                $baseDiscount = $discount * $exchangeRate;
                $baseTax = $tax * $exchangeRate;
                $baseTotal = $total * $exchangeRate;

                SOLine::create([
                    'so_id' => $salesOrder->so_id,
                    'item_id' => $line['item_id'],
                    'unit_price' => $unitPrice,
                    'quantity' => $line['quantity'],
                    'uom_id' => $line['uom_id'],
                    'discount' => $discount,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    // New multicurrency fields
                    'base_currency_unit_price' => $baseUnitPrice,
                    'base_currency_subtotal' => $baseSubtotal,
                    'base_currency_discount' => $baseDiscount,
                    'base_currency_tax' => $baseTax,
                    'base_currency_total' => $baseTotal
                ]);

                $totalAmount += $total;
                $taxAmount += $tax;
            }

            // Update totals
            $baseCurrencyTotal = $totalAmount * $exchangeRate;
            $baseCurrencyTax = $taxAmount * $exchangeRate;

            $salesOrder->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'base_currency_total' => $baseCurrencyTotal,
                'base_currency_tax' => $baseCurrencyTax
            ]);

            // If created from quotation, update quotation status
            if ($request->quotation_id) {
                $quotation = SalesQuotation::find($request->quotation_id);
                $quotation->update(['status' => 'Converted']);
            }

            DB::commit();

            return response()->json([
                'data' => $salesOrder->load('salesOrderLines'),
                'message' => 'Sales order created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a sales order from an existing quotation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createFromQuotation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quotation_id' => 'required|exists:SalesQuotation,quotation_id',
            'so_number' => 'required|unique:SalesOrder,so_number',
            'so_date' => 'required|date',
            'expected_delivery' => 'nullable|date',
            'status' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the quotation
            $quotation = SalesQuotation::with('salesQuotationLines')->find($request->quotation_id);

            if ($quotation->status === 'Converted') {
                return response()->json(['message' => 'This quotation has already been converted to a sales order'], 400);
            }

            $totalAmount = 0;
            $taxAmount = 0;

            // Create sales order
            $salesOrder = SalesOrder::create([
                'so_number' => $request->so_number,
                'so_date' => $request->so_date,
                'customer_id' => $quotation->customer_id,
                'quotation_id' => $quotation->quotation_id,
                'payment_terms' => $quotation->payment_terms,
                'delivery_terms' => $quotation->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => $request->status,
                'total_amount' => 0, // Will be updated later
                'tax_amount' => 0    // Will be updated later
            ]);

            // Create sales order lines from quotation lines
            foreach ($quotation->salesQuotationLines as $quotationLine) {
                // Check if the item is still sellable
                $item = Item::find($quotationLine->item_id);
                if (!$item->is_sellable) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Item ' . $item->name . ' is no longer sellable'
                    ], 422);
                }

                SOLine::create([
                    'so_id' => $salesOrder->so_id,
                    'item_id' => $quotationLine->item_id,
                    'unit_price' => $quotationLine->unit_price,
                    'quantity' => $quotationLine->quantity,
                    'uom_id' => $quotationLine->uom_id,
                    'discount' => $quotationLine->discount,
                    'subtotal' => $quotationLine->subtotal,
                    'tax' => $quotationLine->tax,
                    'total' => $quotationLine->total
                ]);

                $totalAmount += $quotationLine->total;
                $taxAmount += $quotationLine->tax;
            }

            // Update totals
            $salesOrder->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount
            ]);

            // Update quotation status
            $quotation->update(['status' => 'Converted']);

            DB::commit();

            return response()->json([
                'data' => $salesOrder->load('salesOrderLines'),
                'message' => 'Sales order created from quotation successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create sales order from quotation', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified sales order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = SalesOrder::with([
            'customer',
            'salesQuotation',
            'salesOrderLines.item' => function ($query) {
                $query->select('item_id', 'item_code', 'name'); // explicitly select item_code
            },
            'salesOrderLines.unitOfMeasure',
            'salesOrderLines.deliveryLines', // eager load delivery lines for each sales order line
            'deliveries',
            'salesInvoices'
        ])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        return response()->json(['data' => $order], 200);
    }

    /**
     * Update the specified sales order in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be updated (not delivered or invoiced)
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $order->status . ' sales order'], 400);
        }

        $validatorRules = [
            'so_number' => 'required|unique:SalesOrder,so_number,' . $id . ',so_id',
            'so_date' => 'required|date',
            'customer_id' => 'required|exists:Customer,customer_id',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'expected_delivery' => 'nullable|date',
            'status' => 'required|string|max:50',
            'currency_code' => 'nullable|string|size:3', // Add validation for currency
            'lines' => 'required|array',
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

            // Check if currency is changing
            $currencyCode = $request->currency_code ?? $order->currency_code;
            $baseCurrency = config('app.base_currency', 'USD');
            $exchangeRate = $order->exchange_rate;
            $currencyChanged = $currencyCode !== $order->currency_code;

            if ($currencyChanged) {
                // Currency is changing, get new exchange rate
                if ($currencyCode !== $baseCurrency) {
                    $rate = CurrencyRate::where('from_currency', $currencyCode)
                        ->where('to_currency', $baseCurrency)
                        ->where('is_active', true)
                        ->where('effective_date', '<=', $request->so_date)
                        ->where(function ($query) use ($request) {
                            $query->where('end_date', '>=', $request->so_date)
                                ->orWhereNull('end_date');
                        })
                        ->orderBy('effective_date', 'desc')
                        ->first();

                    if (!$rate) {
                        // Try to find a reverse rate
                        $reverseRate = CurrencyRate::where('from_currency', $baseCurrency)
                            ->where('to_currency', $currencyCode)
                            ->where('is_active', true)
                            ->where('effective_date', '<=', $request->so_date)
                            ->where(function ($query) use ($request) {
                                $query->where('end_date', '>=', $request->so_date)
                                    ->orWhereNull('end_date');
                            })
                            ->orderBy('effective_date', 'desc')
                            ->first();

                        if ($reverseRate) {
                            $exchangeRate = 1 / $reverseRate->rate;
                        } else {
                            DB::rollBack();
                            return response()->json([
                                'message' => 'No exchange rate found for the specified currency'
                            ], 422);
                        }
                    } else {
                        $exchangeRate = $rate->rate;
                    }
                } else {
                    // Converting to base currency
                    $exchangeRate = 1.0;
                }
            }

            // Update main order fields
            $order->update([
                'so_number' => $request->so_number,
                'so_date' => $request->so_date,
                'customer_id' => $request->customer_id,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'expected_delivery' => $request->expected_delivery,
                'status' => $request->status,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate
            ]);

            $existingLineIds = $order->salesOrderLines()->pluck('line_id')->toArray();
            $receivedLineIds = [];

            $totalAmount = 0;
            $taxAmount = 0;

            foreach ($request->lines as $line) {
                // Get the item
                $item = Item::find($line['item_id']);

                // Check if the item is sellable
                if (!$item->is_sellable) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Item ' . $item->name . ' is not sellable'
                    ], 422);
                }

                // If unit_price is not provided, get the best sale price for this customer and quantity
                $unitPrice = isset($line['unit_price']) ? $line['unit_price'] : $item->getBestSalePrice($order->customer_id, $line['quantity']);

                // If currency changed, convert the unit price
                if ($currencyChanged && isset($line['line_id'])) {
                    $orderLine = SOLine::find($line['line_id']);
                    if ($orderLine) {
                        // Convert from base currency to new currency
                        $unitPrice = $orderLine->base_currency_unit_price / $exchangeRate;
                    }
                }

                $subtotal = $unitPrice * $line['quantity'];
                $discount = $line['discount'] ?? 0;
                $tax = $line['tax'] ?? 0;
                $total = $subtotal - $discount + $tax;

                // Calculate base currency values
                $baseUnitPrice = $unitPrice * $exchangeRate;
                $baseSubtotal = $subtotal * $exchangeRate;
                $baseDiscount = $discount * $exchangeRate;
                $baseTax = $tax * $exchangeRate;
                $baseTotal = $total * $exchangeRate;

                if (isset($line['line_id']) && in_array($line['line_id'], $existingLineIds)) {
                    // Update existing line
                    $orderLine = SOLine::find($line['line_id']);
                    $orderLine->update([
                        'item_id' => $line['item_id'],
                        'unit_price' => $unitPrice,
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'discount' => $discount,
                        'subtotal' => $subtotal,
                        'tax' => $tax,
                        'total' => $total,
                        'base_currency_unit_price' => $baseUnitPrice,
                        'base_currency_subtotal' => $baseSubtotal,
                        'base_currency_discount' => $baseDiscount,
                        'base_currency_tax' => $baseTax,
                        'base_currency_total' => $baseTotal
                    ]);
                    $receivedLineIds[] = $line['line_id'];
                } else {
                    // Create new line
                    $newLine = SOLine::create([
                        'so_id' => $order->so_id,
                        'item_id' => $line['item_id'],
                        'unit_price' => $unitPrice,
                        'quantity' => $line['quantity'],
                        'uom_id' => $line['uom_id'],
                        'discount' => $discount,
                        'subtotal' => $subtotal,
                        'tax' => $tax,
                        'total' => $total,
                        'base_currency_unit_price' => $baseUnitPrice,
                        'base_currency_subtotal' => $baseSubtotal,
                        'base_currency_discount' => $baseDiscount,
                        'base_currency_tax' => $baseTax,
                        'base_currency_total' => $baseTotal
                    ]);
                    $receivedLineIds[] = $newLine->line_id;
                }

                $totalAmount += $total;
                $taxAmount += $tax;
            }

            // Delete lines that were removed
            $linesToDelete = array_diff($existingLineIds, $receivedLineIds);
            if (!empty($linesToDelete)) {
                SOLine::whereIn('line_id', $linesToDelete)->delete();
            }

            // Calculate base currency totals
            $baseCurrencyTotal = $totalAmount * $exchangeRate;
            $baseCurrencyTax = $taxAmount * $exchangeRate;

            // Update order totals
            $order->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'base_currency_total' => $baseCurrencyTotal,
                'base_currency_tax' => $baseCurrencyTax
            ]);

            DB::commit();

            return response()->json([
                'data' => $order->load('salesOrderLines'),
                'message' => 'Sales order updated successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update sales order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified sales order from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be deleted (no deliveries or invoices)
        if ($order->deliveries->count() > 0) {
            return response()->json(['message' => 'Cannot delete order with related deliveries'], 400);
        }

        if ($order->salesInvoices->count() > 0) {
            return response()->json(['message' => 'Cannot delete order with related invoices'], 400);
        }

        try {
            DB::beginTransaction();

            // Delete related order lines
            $order->salesOrderLines()->delete();

            // Delete the order
            $order->delete();

            DB::commit();

            return response()->json(['message' => 'Sales order deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete sales order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Add a new line to the specified sales order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function addLine(Request $request, $id)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be updated (not delivered or invoiced)
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $order->status . ' sales order'], 400);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'uom_id' => 'required|exists:unit_of_measures,uom_id',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the item
            $item = Item::find($request->item_id);

            // Check if the item is sellable
            if (!$item->is_sellable) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Item ' . $item->name . ' is not sellable'
                ], 422);
            }

            // If unit_price is not provided, get the best sale price for this customer and quantity
            $unitPrice = $request->unit_price ?? $item->getBestSalePrice($order->customer_id, $request->quantity);

            $subtotal = $unitPrice * $request->quantity;
            $discount = $request->discount ?? 0;
            $tax = $request->tax ?? 0;
            $total = $subtotal - $discount + $tax;

            $line = SOLine::create([
                'so_id' => $id,
                'item_id' => $request->item_id,
                'unit_price' => $unitPrice,
                'quantity' => $request->quantity,
                'uom_id' => $request->uom_id,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total
            ]);

            // Update order totals
            $order->total_amount += $total;
            $order->tax_amount += $tax;
            $order->save();

            DB::commit();

            return response()->json(['data' => $line, 'message' => 'Line added to sales order successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to add line to sales order', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a line in the specified sales order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @param  int  $lineId
     * @return \Illuminate\Http\Response
     */
    public function updateLine(Request $request, $id, $lineId)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be updated (not delivered or invoiced)
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $order->status . ' sales order'], 400);
        }

        $line = SOLine::where('so_id', $id)->where('line_id', $lineId)->first();

        if (!$line) {
            return response()->json(['message' => 'Order line not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,item_id',
            'unit_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'uom_id' => 'required|exists:unit_of_measures,uom_id',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Get the item
            $item = Item::find($request->item_id);

            // Check if the item is sellable
            if (!$item->is_sellable) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Item ' . $item->name . ' is not sellable'
                ], 422);
            }

            // If unit_price is not provided, get the best sale price for this customer and quantity
            $unitPrice = $request->unit_price ?? $item->getBestSalePrice($order->customer_id, $request->quantity);

            // Calculate new values
            $subtotal = $unitPrice * $request->quantity;
            $discount = $request->discount ?? 0;
            $tax = $request->tax ?? 0;
            $total = $subtotal - $discount + $tax;

            // Update order totals
            $order->total_amount = $order->total_amount - $line->total + $total;
            $order->tax_amount = $order->tax_amount - $line->tax + $tax;
            $order->save();

            // Update line
            $line->update([
                'item_id' => $request->item_id,
                'unit_price' => $unitPrice,
                'quantity' => $request->quantity,
                'uom_id' => $request->uom_id,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total
            ]);

            DB::commit();

            return response()->json(['data' => $line, 'message' => 'Order line updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update order line', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove a line from the specified sales order.
     *
     * @param  int  $id
     * @param  int  $lineId
     * @return \Illuminate\Http\Response
     */
    public function removeLine($id, $lineId)
    {
        $order = SalesOrder::find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Check if order can be updated (not delivered or invoiced)
        if (in_array($order->status, ['Delivered', 'Invoiced', 'Closed'])) {
            return response()->json(['message' => 'Cannot update a ' . $order->status . ' sales order'], 400);
        }

        $line = SOLine::where('so_id', $id)->where('line_id', $lineId)->first();

        if (!$line) {
            return response()->json(['message' => 'Order line not found'], 404);
        }

        try {
            DB::beginTransaction();

            // Update order totals
            $order->total_amount -= $line->total;
            $order->tax_amount -= $line->tax;
            $order->save();

            // Delete the line
            $line->delete();

            DB::commit();

            return response()->json(['message' => 'Order line removed successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to remove order line', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Convert sales order currency.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function convertCurrency(Request $request, $id)
    {
        $salesOrder = SalesOrder::find($id);

        if (!$salesOrder) {
            return response()->json(['message' => 'Sales order not found'], 404);
        }

        // Only allow currency conversion for draft and confirmed orders
        if (!in_array($salesOrder->status, ['Draft', 'Confirmed'])) {
            return response()->json([
                'message' => 'Only Draft or Confirmed sales orders can have their currency converted'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'currency_code' => 'required|string|size:3',
            'use_exchange_rate_date' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Don't convert if already in the target currency
        if ($salesOrder->currency_code === $request->currency_code) {
            return response()->json([
                'message' => 'Sales order is already in the specified currency',
                'data' => $salesOrder
            ]);
        }

        $baseCurrency = config('app.base_currency', 'USD');

        try {
            DB::beginTransaction();

            // Determine which exchange rate to use
            $useExchangeRateDate = $request->use_exchange_rate_date ?? true;
            $exchangeRateDate = $useExchangeRateDate ? $salesOrder->so_date : now()->format('Y-m-d');

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
            $newTotalAmount = $salesOrder->base_currency_total / $newExchangeRate;
            $newTaxAmount = $salesOrder->base_currency_tax / $newExchangeRate;

            // Update sales order
            $salesOrder->update([
                'currency_code' => $request->currency_code,
                'exchange_rate' => $newExchangeRate,
                'total_amount' => $newTotalAmount,
                'tax_amount' => $newTaxAmount
            ]);

            // Update all line items
            foreach ($salesOrder->salesOrderLines as $line) {
                $newUnitPrice = $line->base_currency_unit_price / $newExchangeRate;
                $newSubtotal = $line->base_currency_subtotal / $newExchangeRate;
                $newDiscount = $line->base_currency_discount / $newExchangeRate;
                $newTax = $line->base_currency_tax / $newExchangeRate;
                $newTotal = $line->base_currency_total / $newExchangeRate;

                $line->update([
                    'unit_price' => $newUnitPrice,
                    'subtotal' => $newSubtotal,
                    'discount' => $newDiscount,
                    'tax' => $newTax,
                    'total' => $newTotal
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Sales order currency converted successfully',
                'data' => $salesOrder->fresh()->load('salesOrderLines')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to convert sales order currency', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get outstanding items for a specific sales order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getOutstandingItems($id)
    {
        $order = SalesOrder::with(['salesOrderLines.item', 'customer'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Sales order tidak ditemukan'], 404);
        }

        $outstandingItems = [];
        $allDelivered = true;

        foreach ($order->salesOrderLines as $line) {
            $orderedQty = $line->quantity;
            $deliveredQty = DeliveryLine::join('Delivery', 'DeliveryLine.delivery_id', '=', 'Delivery.delivery_id')
                ->where('DeliveryLine.so_line_id', $line->line_id)
                ->where('Delivery.status', 'Completed')
                ->sum('DeliveryLine.delivered_quantity');

            $outstandingQty = $orderedQty - $deliveredQty;

            // Hanya masukkan item yang masih memiliki outstanding quantity
            if ($outstandingQty > 0) {
                $allDelivered = false;

                // Get available stock in warehouses
                $warehouseStocks = ItemStock::where('item_id', $line->item_id)
                    ->where('quantity', '>', 0)
                    ->with('warehouse')
                    ->get()
                    ->map(function ($stock) {
                        return [
                            'warehouse_id' => $stock->warehouse_id,
                            'warehouse_name' => $stock->warehouse->name,
                            'available_quantity' => $stock->quantity - $stock->reserved_quantity,
                            'total_quantity' => $stock->quantity
                        ];
                    });

                $outstandingItems[] = [
                    'so_id' => $order->so_id,
                    'so_number' => $order->so_number,
                    'so_line_id' => $line->line_id,
                    'item_id' => $line->item_id,
                    'item_name' => $line->item->name,
                    'item_code' => $line->item->item_code,
                    'ordered_quantity' => $orderedQty,
                    'delivered_quantity' => $deliveredQty,
                    'outstanding_quantity' => $outstandingQty,
                    'uom_id' => $line->uom_id,
                    'warehouse_stocks' => $warehouseStocks
                ];
            }
        }

        return response()->json([
            'data' => [
                'so_id' => $order->so_id,
                'so_number' => $order->so_number,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer->name,
                'status' => $order->status,
                'is_fully_delivered' => $allDelivered,
                'outstanding_items' => $outstandingItems
            ]
        ], 200);
    }

    /**
     * Get all outstanding sales orders.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllOutstandingSalesOrders()
    {
        // Ambil semua sales order yang belum fully delivered
        $orders = SalesOrder::whereNotIn('status', ['Delivered', 'Closed', 'Cancelled'])
            ->with(['customer'])
            ->get();

        $outstandingSalesOrders = [];

        foreach ($orders as $order) {
            $hasOutstanding = false;
            $outstandingItems = [];
            $totalOutstandingQty = 0;

            // Hitung outstanding quantity untuk setiap line item
            foreach ($order->salesOrderLines as $line) {
                $orderedQty = $line->quantity;
                $deliveredQty = DeliveryLine::join('Delivery', 'DeliveryLine.delivery_id', '=', 'Delivery.delivery_id')
                    ->where('DeliveryLine.so_line_id', $line->line_id)
                    ->where('Delivery.status', 'Completed')
                    ->sum('DeliveryLine.delivered_quantity');

                $outstandingQty = $orderedQty - $deliveredQty;

                if ($outstandingQty > 0) {
                    $hasOutstanding = true;
                    $totalOutstandingQty += $outstandingQty;
                    $outstandingItems[] = [
                        'so_line_id' => $line->line_id,
                        'item_id' => $line->item_id,
                        'item_name' => $line->item->name,
                        'item_code' => $line->item->item_code,
                        'ordered_quantity' => $orderedQty,
                        'delivered_quantity' => $deliveredQty,
                        'outstanding_quantity' => $outstandingQty
                    ];
                }
            }

            // Hanya tambahkan sales order yang memiliki outstanding items
            if ($hasOutstanding) {
                $outstandingSalesOrders[] = [
                    'so_id' => $order->so_id,
                    'so_number' => $order->so_number,
                    'so_date' => $order->so_date,
                    'customer_id' => $order->customer_id,
                    'customer_name' => $order->customer->name,
                    'status' => $order->status,
                    'outstanding_items_count' => count($outstandingItems),
                    'total_outstanding_quantity' => $totalOutstandingQty,
                ];
            }
        }

        return response()->json([
            'data' => $outstandingSalesOrders
        ], 200);
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

            // Header columns for Sales Order
            $headers = [
                'A1' => 'SO Number*',
                'B1' => 'SO Date*',
                'C1' => 'Customer Code*',
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
            $columnWidths = ['A' => 15, 'B' => 12, 'C' => 15, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 12, 'H' => 12, 'I' => 25];
            foreach ($columnWidths as $column => $width) {
                $headerSheet->getColumnDimension($column)->setWidth($width);
            }

            // Add sample data
            $sampleData = [
                ['SO-2024-001', '2024-01-15', 'CUST001', 'Net 30', 'FOB Destination', '2024-02-15', 'Draft', 'USD', 'Sample sales order 1'],
                ['SO-2024-002', '2024-01-16', 'CUST002', 'Net 60', 'FOB Origin', '2024-02-20', 'Confirmed', 'EUR', 'Sample sales order 2']
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
                'A1' => 'SO Number*',
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
                ['SO-2024-001', 'ITEM001', 10, 'PCS', 100.00, 0, 10.00, 'Line 1 for SO-2024-001'],
                ['SO-2024-001', 'ITEM002', 5, 'KG', 50.00, 5.00, 2.50, 'Line 2 for SO-2024-001'],
                ['SO-2024-002', 'ITEM003', 20, 'PCS', 75.00, 0, 15.00, 'Line 1 for SO-2024-002']
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

            // ===== SHEET 3: REFERENCE DATA =====
            $refSheet = $spreadsheet->createSheet();
            $refSheet->setTitle('Reference Data');

            // Customers reference
            $refSheet->setCellValue('A1', 'CUSTOMERS');
            $refSheet->setCellValue('A2', 'Customer Code');
            $refSheet->setCellValue('B2', 'Customer Name');
            $refSheet->getStyle('A1:B2')->applyFromArray($headerStyle);

            $customers = Customer::select('customer_id', 'customer_code', 'name')->limit(50)->get();
            $row = 3;
            foreach ($customers as $customer) {
                $customerCode = $customer->customer_code ?: 'CUST' . str_pad($customer->customer_id, 3, '0', STR_PAD_LEFT);
                $refSheet->setCellValue('A' . $row, $customerCode);
                $refSheet->setCellValue('B' . $row, $customer->name);
                $row++;
            }

            // Items reference
            $refSheet->setCellValue('D1', 'ITEMS');
            $refSheet->setCellValue('D2', 'Item Code');
            $refSheet->setCellValue('E2', 'Item Name');
            $refSheet->setCellValue('F2', 'Sale Price');
            $refSheet->getStyle('D1:F2')->applyFromArray($headerStyle);

            $items = Item::where('is_sellable', true)->select('item_id', 'item_code', 'name', 'sale_price')->limit(100)->get();
            $row = 3;
            foreach ($items as $item) {
                $refSheet->setCellValue('D' . $row, $item->item_code);
                $refSheet->setCellValue('E' . $row, $item->name);
                $refSheet->setCellValue('F' . $row, $item->sale_price ?? 0);
                $row++;
            }

            // UOM reference
            $refSheet->setCellValue('H1', 'UNIT OF MEASURES');
            $refSheet->setCellValue('H2', 'UOM Code');
            $refSheet->setCellValue('I2', 'UOM Name');
            $refSheet->getStyle('H1:I2')->applyFromArray($headerStyle);

            $uoms = UnitOfMeasure::select('symbol', 'name')->get();
            $row = 3;
            foreach ($uoms as $uom) {
                $refSheet->setCellValue('H' . $row, $uom->symbol);
                $refSheet->setCellValue('I' . $row, $uom->name);
                $row++;
            }

            // Status reference
            $refSheet->setCellValue('K1', 'VALID STATUSES');
            $refSheet->setCellValue('K2', 'Status');
            $refSheet->getStyle('K1:K2')->applyFromArray($headerStyle);

            $statuses = ['Draft', 'Confirmed', 'In Progress', 'Delivered', 'Invoiced', 'Closed', 'Cancelled'];
            $row = 3;
            foreach ($statuses as $status) {
                $refSheet->setCellValue('K' . $row, $status);
                $row++;
            }

            // Currency reference
            $refSheet->setCellValue('M1', 'CURRENCIES');
            $refSheet->setCellValue('M2', 'Currency Code');
            $refSheet->getStyle('M1:M2')->applyFromArray($headerStyle);

            $currencies = ['USD', 'EUR', 'IDR', 'SGD', 'MYR', 'JPY', 'CNY'];
            $row = 3;
            foreach ($currencies as $currency) {
                $refSheet->setCellValue('M' . $row, $currency);
                $row++;
            }

            // Set active sheet back to first sheet
            $spreadsheet->setActiveSheetIndex(0);

            // Add instructions sheet
            $instructionSheet = $spreadsheet->createSheet();
            $instructionSheet->setTitle('Instructions');

            $instructions = [
                'SALES ORDER IMPORT INSTRUCTIONS',
                '',
                '1. GENERAL RULES:',
                '   - Fields marked with * are required',
                '   - Use the exact codes from Reference Data sheet',
                '   - Date format: YYYY-MM-DD (e.g., 2024-01-15)',
                '   - Decimal numbers use dot (.) as separator',
                '',
                '2. SALES ORDERS SHEET:',
                '   - SO Number: Must be unique across all sales orders',
                '   - Customer Code: Must exist in Reference Data',
                '   - Status: Use values from Reference Data sheet',
                '   - Currency Code: Use standard 3-letter codes (USD, EUR, etc.)',
                '',
                '3. SALES ORDER LINES SHEET:',
                '   - SO Number: Must match exactly with SO Number in Sales Orders sheet',
                '   - Item Code: Must exist and be sellable',
                '   - UOM Code: Must exist in Reference Data',
                '   - Unit Price: If empty, system will use default sale price',
                '   - Discount and Tax: Optional, use 0 if not applicable',
                '',
                '4. IMPORT PROCESS:',
                '   - System will first create Sales Orders from "Sales Orders" sheet',
                '   - Then add lines from "Sales Order Lines" sheet',
                '   - If SO Number already exists, you can choose to update or skip',
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
                        'font' => ['bold' => true, 'size' => 14],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '366092']],
                        'font' => ['color' => ['rgb' => 'FFFFFF']]
                    ]);
                } elseif (strpos($instruction, ':') !== false && !empty(trim($instruction))) {
                    $instructionSheet->getStyle('A' . $row)->applyFromArray([
                        'font' => ['bold' => true]
                    ]);
                }
                $row++;
            }
            $instructionSheet->getColumnDimension('A')->setWidth(80);

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
            return response()->json([
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
                    $soNumber = trim($headerSheet->getCell('A' . $row)->getValue() ?? '');
                    $soDate = $headerSheet->getCell('B' . $row)->getFormattedValue();
                    $customerCode = trim($headerSheet->getCell('C' . $row)->getValue() ?? '');
                    $paymentTerms = trim($headerSheet->getCell('D' . $row)->getValue() ?? '');
                    $deliveryTerms = trim($headerSheet->getCell('E' . $row)->getValue() ?? '');
                    $expectedDelivery = $headerSheet->getCell('F' . $row)->getFormattedValue();
                    $status = trim($headerSheet->getCell('G' . $row)->getValue() ?? 'Draft');
                    $currencyCode = trim($headerSheet->getCell('H' . $row)->getValue() ?? 'USD');

                    // Skip empty rows
                    if (empty($soNumber) && empty($customerCode)) {
                        continue;
                    }

                    // Validate required fields
                    if (empty($soNumber) || empty($soDate) || empty($customerCode)) {
                        $errors[] = "Row {$row}: Missing required fields (SO Number, SO Date, or Customer Code)";
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

                    // Check if SO already exists
                    $existingSO = SalesOrder::where('so_number', $soNumber)->first();
                    if ($existingSO && !$updateExisting) {
                        $errors[] = "Row {$row}: Sales Order '{$soNumber}' already exists. Enable 'Update Existing' to overwrite.";
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

                    // Create or update Sales Order
                    $salesOrderData = [
                        'so_number' => $soNumber,
                        'so_date' => $soDateFormatted,
                        'customer_id' => $customer->customer_id,
                        'payment_terms' => $paymentTerms,
                        'delivery_terms' => $deliveryTerms,
                        'expected_delivery' => $expectedDeliveryFormatted,
                        'status' => $status,
                        'currency_code' => $currencyCode,
                        'exchange_rate' => $exchangeRate,
                        'base_currency' => $baseCurrency,
                        'total_amount' => 0, // Will be calculated after adding lines
                        'tax_amount' => 0
                    ];

                    if ($existingSO && $updateExisting) {
                        $existingSO->update($salesOrderData);
                        $salesOrder = $existingSO;
                        // Delete existing lines to replace with new ones
                        $salesOrder->salesOrderLines()->delete();
                    } else {
                        $salesOrder = SalesOrder::create($salesOrderData);
                    }

                    $createdOrders[$soNumber] = $salesOrder;
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
                        $soNumber = trim($linesSheet->getCell('A' . $row)->getValue() ?? '');
                        $itemCode = trim($linesSheet->getCell('B' . $row)->getValue() ?? '');
                        $quantity = $linesSheet->getCell('C' . $row)->getValue();
                        $uomCode = trim($linesSheet->getCell('D' . $row)->getValue() ?? '');
                        $unitPrice = $linesSheet->getCell('E' . $row)->getValue();
                        $discount = $linesSheet->getCell('F' . $row)->getValue() ?? 0;
                        $tax = $linesSheet->getCell('G' . $row)->getValue() ?? 0;

                        // Skip empty rows
                        if (empty($soNumber) && empty($itemCode)) {
                            continue;
                        }

                        // Validate required fields
                        if (empty($soNumber) || empty($itemCode) || empty($quantity) || empty($uomCode)) {
                            $errors[] = "Lines Row {$row}: Missing required fields";
                            $errorCount++;
                            continue;
                        }

                        // Find the sales order
                        if (!isset($createdOrders[$soNumber])) {
                            $salesOrder = SalesOrder::where('so_number', $soNumber)->first();
                            if (!$salesOrder) {
                                $errors[] = "Lines Row {$row}: Sales Order '{$soNumber}' not found";
                                $errorCount++;
                                continue;
                            }
                        } else {
                            $salesOrder = $createdOrders[$soNumber];
                        }

                        // Find item
                        $item = Item::where('item_code', $itemCode)->where('is_sellable', true)->first();
                        if (!$item) {
                            $errors[] = "Lines Row {$row}: Sellable item with code '{$itemCode}' not found";
                            $errorCount++;
                            continue;
                        }

                        // Find UOM
                        $uom = UnitOfMeasure::where('symbol', $uomCode)->first();
                        if (!$uom) {
                            $errors[] = "Lines Row {$row}: UOM with code '{$uomCode}' not found";
                            $errorCount++;
                            continue;
                        }

                        // Use default price if not provided
                        if (empty($unitPrice)) {
                            $unitPrice = $item->getBestSalePriceInCurrency($salesOrder->customer_id, $quantity, $salesOrder->currency_code);
                        }

                        // Calculate line totals
                        $subtotal = $unitPrice * $quantity;
                        $total = $subtotal - $discount + $tax;

                        // Calculate base currency values
                        $baseUnitPrice = $unitPrice * $salesOrder->exchange_rate;
                        $baseSubtotal = $subtotal * $salesOrder->exchange_rate;
                        $baseDiscount = $discount * $salesOrder->exchange_rate;
                        $baseTax = $tax * $salesOrder->exchange_rate;
                        $baseTotal = $total * $salesOrder->exchange_rate;

                        // Create line
                        SOLine::create([
                            'so_id' => $salesOrder->so_id,
                            'item_id' => $item->item_id,
                            'unit_price' => $unitPrice,
                            'quantity' => $quantity,
                            'uom_id' => $uom->uom_id,
                            'discount' => $discount,
                            'subtotal' => $subtotal,
                            'tax' => $tax,
                            'total' => $total,
                            'base_currency_unit_price' => $baseUnitPrice,
                            'base_currency_subtotal' => $baseSubtotal,
                            'base_currency_discount' => $baseDiscount,
                            'base_currency_tax' => $baseTax,
                            'base_currency_total' => $baseTotal
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = "Lines Row {$row}: " . $e->getMessage();
                        $errorCount++;
                    }
                }
            }

            // Update totals for all sales orders
            foreach ($createdOrders as $salesOrder) {
                $totalAmount = $salesOrder->salesOrderLines()->sum('total');
                $taxAmount = $salesOrder->salesOrderLines()->sum('tax');
                $baseCurrencyTotal = $salesOrder->salesOrderLines()->sum('base_currency_total');
                $baseCurrencyTax = $salesOrder->salesOrderLines()->sum('base_currency_tax');

                $salesOrder->update([
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'base_currency_total' => $baseCurrencyTotal,
                    'base_currency_tax' => $baseCurrencyTax
                ]);
            }

            DB::commit();

            return response()->json([
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
            return response()->json([
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
                'B1' => 'SO Date',
                'C1' => 'Customer Code',
                'D1' => 'Customer Name',
                'E1' => 'Payment Terms',
                'F1' => 'Delivery Terms',
                'G1' => 'Expected Delivery',
                'H1' => 'Status',
                'I1' => 'Currency',
                'J1' => 'Total Amount',
                'K1' => 'Tax Amount'
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
            $orderSheet->getStyle('A1:K1')->applyFromArray($headerStyle);

            // Add data
            $row = 2;
            foreach ($salesOrders as $order) {
                $orderSheet->setCellValue('A' . $row, $order->so_number);
                $orderSheet->setCellValue('B' . $row, $order->so_date->format('Y-m-d'));
                $orderSheet->setCellValue('C' . $row, $order->customer->customer_code ?? '');
                $orderSheet->setCellValue('D' . $row, $order->customer->name);
                $orderSheet->setCellValue('E' . $row, $order->payment_terms);
                $orderSheet->setCellValue('F' . $row, $order->delivery_terms);
                $orderSheet->setCellValue('G' . $row, $order->expected_delivery ? $order->expected_delivery->format('Y-m-d') : '');
                $orderSheet->setCellValue('H' . $row, $order->status);
                $orderSheet->setCellValue('I' . $row, $order->currency_code ?? 'USD');
                $orderSheet->setCellValue('J' . $row, $order->total_amount);
                $orderSheet->setCellValue('K' . $row, $order->tax_amount);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'K') as $column) {
                $orderSheet->getColumnDimension($column)->setAutoSize(true);
            }

            // ===== SHEET 2: SALES ORDER LINES =====
            $linesSheet = $spreadsheet->createSheet();
            $linesSheet->setTitle('Sales Order Lines');

            $lineHeaders = [
                'A1' => 'SO Number',
                'B1' => 'Item Code',
                'C1' => 'Item Name',
                'D1' => 'Quantity',
                'E1' => 'UOM',
                'F1' => 'Unit Price',
                'G1' => 'Discount',
                'H1' => 'Subtotal',
                'I1' => 'Tax',
                'J1' => 'Total'
            ];

            foreach ($lineHeaders as $cell => $value) {
                $linesSheet->setCellValue($cell, $value);
            }

            $linesSheet->getStyle('A1:J1')->applyFromArray($headerStyle);

            $row = 2;
            foreach ($salesOrders as $order) {
                foreach ($order->salesOrderLines as $line) {
                    $linesSheet->setCellValue('A' . $row, $order->so_number);
                    $linesSheet->setCellValue('B' . $row, $line->item->item_code);
                    $linesSheet->setCellValue('C' . $row, $line->item->name);
                    $linesSheet->setCellValue('D' . $row, $line->quantity);
                    $linesSheet->setCellValue('E' . $row, $line->unitOfMeasure->symbol);
                    $linesSheet->setCellValue('F' . $row, $line->unit_price);
                    $linesSheet->setCellValue('G' . $row, $line->discount);
                    $linesSheet->setCellValue('H' . $row, $line->subtotal);
                    $linesSheet->setCellValue('I' . $row, $line->tax);
                    $linesSheet->setCellValue('J' . $row, $line->total);
                    $row++;
                }
            }

            // Auto-size columns
            foreach (range('A', 'J') as $column) {
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
            return response()->json([
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VendorQuotation;
use App\Models\RequestForQuotation;
use App\Models\Vendor;
use App\Models\CurrencyRate;
use App\Http\Requests\VendorQuotationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VendorQuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorQuotation::with(['vendor', 'requestForQuotation']);
        
        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }
        
        if ($request->has('rfq_id')) {
            $query->where('rfq_id', $request->rfq_id);
        }
        
        if ($request->has('date_from')) {
            $query->whereDate('quotation_date', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('quotation_date', '<=', $request->date_to);
        }
        
        // Filter by currency
        if ($request->has('currency_code')) {
            $query->where('currency_code', $request->currency_code);
        }
        
        // Apply sorting
        $sortField = $request->input('sort_field', 'quotation_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $vendorQuotations = $query->paginate($perPage);
        
        // Add currency conversion info if requested
        if ($request->has('display_currency') && $request->display_currency !== config('app.base_currency')) {
            $displayCurrency = $request->display_currency;
            $exchangeDate = $request->input('exchange_date', now()->format('Y-m-d'));
            
            foreach ($vendorQuotations->items() as $quotation) {
                $quotation->display_amounts = $this->convertQuotationAmounts(
                    $quotation, 
                    $displayCurrency, 
                    $exchangeDate
                );
            }
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $vendorQuotations
        ]);
    }

    public function createFromRFQ(Request $request)
    {
        $request->validate([
            'rfq_id' => 'required|exists:request_for_quotations,rfq_id',
            'vendor_id' => 'required|exists:vendors,vendor_id',
            'quotation_date' => 'required|date',
            'validity_date' => 'nullable|date',
            'currency_code' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string',
            'delivery_terms' => 'nullable|string',
            'lines' => 'nullable|array',
            'lines.*.item_id' => 'required_with:lines|exists:items,item_id',
            'lines.*.unit_price' => 'required_with:lines|numeric',
            'lines.*.uom_id' => 'required_with:lines|exists:unit_of_measures,uom_id',
            'lines.*.quantity' => 'required_with:lines|numeric',
            'lines.*.delivery_date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $rfq = RequestForQuotation::findOrFail($request->rfq_id);

            // Check RFQ status
            if ($rfq->status !== 'sent') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor quotations can only be created for RFQs with status "sent"'
                ], 400);
            }

            $vendor = Vendor::findOrFail($request->vendor_id);

            if ($vendor->status !== 'active') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor quotations can only be created for active vendors'
                ], 400);
            }

            // Check if quotation already exists for this vendor and RFQ
            $existingQuotation = VendorQuotation::where('rfq_id', $request->rfq_id)
                ->where('vendor_id', $request->vendor_id)
                ->first();

            if ($existingQuotation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A quotation from this vendor for this RFQ already exists',
                    'data' => [
                        'existing_quotation_id' => $existingQuotation->quotation_id,
                        'existing_quotation_date' => $existingQuotation->quotation_date,
                        'existing_quotation_status' => $existingQuotation->status
                    ]
                ], 400);
            }

            $exchangeRate = $request->exchange_rate;
            $currencyCode = $request->currency_code ?? $vendor->preferred_currency ?? config('app.base_currency');

            if (!$exchangeRate && $currencyCode !== config('app.base_currency')) {
                try {
                    $exchangeRate = $this->getExchangeRate(
                        $currencyCode,
                        config('app.base_currency'),
                        $request->quotation_date
                    );
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Exchange rate is required but could not be retrieved automatically: ' . $e->getMessage(),
                        'details' => [
                            'currency_code' => $currencyCode,
                            'quotation_date' => $request->quotation_date,
                            'suggestion' => 'Please provide exchange_rate in the request'
                        ]
                    ], 422);
                }
            } else if ($currencyCode === config('app.base_currency')) {
                $exchangeRate = 1.0;
            }

            $totalAmount = 0;
            if ($request->has('lines') && is_array($request->lines)) {
                foreach ($request->lines as $line) {
                    $lineSubtotal = ($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0);
                    $totalAmount += $lineSubtotal;
                }
            }

            $vendorQuotation = VendorQuotation::create([
                'rfq_id' => $request->rfq_id,
                'vendor_id' => $request->vendor_id,
                'quotation_date' => $request->quotation_date,
                'validity_date' => $request->validity_date,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'status' => 'received',
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'total_amount' => $totalAmount,
                'base_currency_total' => $totalAmount * $exchangeRate
            ]);

            if ($request->has('lines') && is_array($request->lines)) {
                foreach ($request->lines as $line) {
                    $lineQuantity = $line['quantity'] ?? 0;
                    $lineUnitPrice = $line['unit_price'] ?? 0;
                    $lineSubtotal = $lineQuantity * $lineUnitPrice;
                    $baseCurrencyUnitPrice = $lineUnitPrice * $exchangeRate;
                    $baseCurrencySubtotal = $lineSubtotal * $exchangeRate;

                    $vendorQuotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $lineUnitPrice,
                        'uom_id' => $line['uom_id'],
                        'quantity' => $lineQuantity,
                        'delivery_date' => $line['delivery_date'] ?? null,
                        'subtotal' => $lineSubtotal,
                        'base_currency_unit_price' => $baseCurrencyUnitPrice,
                        'base_currency_subtotal' => $baseCurrencySubtotal
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Vendor Quotation created successfully from RFQ',
                'data' => $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Vendor Quotation from RFQ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(VendorQuotationRequest $request)
    {
        try {
            DB::beginTransaction();
            
            // Enhanced validation: Check if RFQ exists and is in sent status
            $rfq = RequestForQuotation::findOrFail($request->rfq_id);
            if ($rfq->status !== 'sent') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor quotations can only be created for RFQs with status "sent"'
                ], 400);
            }
            
            // Enhanced validation: Check if vendor exists and is active
            $vendor = Vendor::findOrFail($request->vendor_id);
            if ($vendor->status !== 'active') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Vendor quotations can only be created for active vendors'
                ], 400);
            }
            
            // Enhanced validation: Check if vendor quotation already exists for this vendor and RFQ
            $existingQuotation = VendorQuotation::where('rfq_id', $request->rfq_id)
                                                 ->where('vendor_id', $request->vendor_id)
                                                 ->first();
            
            if ($existingQuotation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A quotation from this vendor for this RFQ already exists',
                    'data' => [
                        'existing_quotation_id' => $existingQuotation->quotation_id,
                        'existing_quotation_date' => $existingQuotation->quotation_date,
                        'existing_quotation_status' => $existingQuotation->status
                    ]
                ], 400);
            }
            
            // Get exchange rate if not provided
            $exchangeRate = $request->exchange_rate;
            $currencyCode = $request->currency_code ?? $vendor->preferred_currency ?? config('app.base_currency');
            
            if (!$exchangeRate && $currencyCode !== config('app.base_currency')) {
                try {
                    $exchangeRate = $this->getExchangeRate(
                        $currencyCode, 
                        config('app.base_currency'), 
                        $request->quotation_date
                    );
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Exchange rate is required but could not be retrieved automatically: ' . $e->getMessage(),
                        'details' => [
                            'currency_code' => $currencyCode,
                            'quotation_date' => $request->quotation_date,
                            'suggestion' => 'Please provide exchange_rate in the request'
                        ]
                    ], 422);
                }
            } else if ($currencyCode === config('app.base_currency')) {
                $exchangeRate = 1.0;
            }
            
            // Calculate totals if lines are provided
            $totalAmount = 0;
            if ($request->has('lines') && is_array($request->lines)) {
                foreach ($request->lines as $line) {
                    $lineSubtotal = ($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0);
                    $totalAmount += $lineSubtotal;
                }
            }
            
            // Create vendor quotation
            $vendorQuotation = VendorQuotation::create([
                'rfq_id' => $request->rfq_id,
                'vendor_id' => $request->vendor_id,
                'quotation_date' => $request->quotation_date,
                'validity_date' => $request->validity_date,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'status' => 'received',
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'total_amount' => $totalAmount,
                'base_currency_total' => $totalAmount * $exchangeRate
            ]);
            
            Log::info('Vendor quotation created', [
                'quotation_id' => $vendorQuotation->quotation_id,
                'rfq_id' => $request->rfq_id,
                'vendor_id' => $request->vendor_id,
                'currency_code' => $currencyCode,
                'total_amount' => $totalAmount
            ]);
            
            // Create quotation lines if provided
            if ($request->has('lines') && is_array($request->lines)) {
                foreach ($request->lines as $line) {
                    $lineQuantity = $line['quantity'] ?? 0;
                    $lineUnitPrice = $line['unit_price'] ?? 0;
                    $lineSubtotal = $lineQuantity * $lineUnitPrice;
                    $baseCurrencyUnitPrice = $lineUnitPrice * $exchangeRate;
                    $baseCurrencySubtotal = $lineSubtotal * $exchangeRate;
                    
                    $vendorQuotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $lineUnitPrice,
                        'uom_id' => $line['uom_id'],
                        'quantity' => $lineQuantity,
                        'delivery_date' => $line['delivery_date'] ?? null,
                        'subtotal' => $lineSubtotal,
                        'base_currency_unit_price' => $baseCurrencyUnitPrice,
                        'base_currency_subtotal' => $baseCurrencySubtotal
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Vendor quotation successfully created with lines', [
                'quotation_id' => $vendorQuotation->quotation_id,
                'lines_count' => count($request->lines ?? [])
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor Quotation created successfully',
                'data' => $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create vendor quotation', [
                'error' => $e->getMessage(),
                'rfq_id' => $request->rfq_id ?? null,
                'vendor_id' => $request->vendor_id ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Vendor Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(VendorQuotation $vendorQuotation)
    {
        $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure']);
        
        return response()->json([
            'status' => 'success',
            'data' => $vendorQuotation
        ]);
    }

    public function update(VendorQuotationRequest $request, VendorQuotation $vendorQuotation)
    {
        // Check if quotation can be updated (only received status)
        if ($vendorQuotation->status !== 'received') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only quotations with status "received" can be updated'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Recalculate exchange rate if currency changed
            $exchangeRate = $request->exchange_rate ?? $vendorQuotation->exchange_rate;
            if ($request->has('currency_code') && $request->currency_code !== $vendorQuotation->currency_code) {
                try {
                    $exchangeRate = $this->getExchangeRate(
                        $request->currency_code, 
                        config('app.base_currency'), 
                        $request->quotation_date ?? $vendorQuotation->quotation_date
                    );
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Exchange rate is required but could not be retrieved: ' . $e->getMessage()
                    ], 422);
                }
            }
            
            // Calculate new total if lines are provided
            $totalAmount = $vendorQuotation->total_amount;
            if ($request->has('lines') && is_array($request->lines)) {
                $totalAmount = 0;
                foreach ($request->lines as $line) {
                    $lineSubtotal = ($line['quantity'] ?? 0) * ($line['unit_price'] ?? 0);
                    $totalAmount += $lineSubtotal;
                }
            }
            
            // Update quotation details
            $vendorQuotation->update([
                'quotation_date' => $request->quotation_date ?? $vendorQuotation->quotation_date,
                'validity_date' => $request->validity_date ?? $vendorQuotation->validity_date,
                'currency_code' => $request->currency_code ?? $vendorQuotation->currency_code,
                'exchange_rate' => $exchangeRate,
                'notes' => $request->notes ?? $vendorQuotation->notes,
                'payment_terms' => $request->payment_terms ?? $vendorQuotation->payment_terms,
                'delivery_terms' => $request->delivery_terms ?? $vendorQuotation->delivery_terms,
                'total_amount' => $totalAmount,
                'base_currency_total' => $totalAmount * $exchangeRate
            ]);
            
            // Update quotation lines if provided
            if ($request->has('lines') && is_array($request->lines)) {
                // Delete existing lines
                $vendorQuotation->lines()->delete();
                
                // Create new lines
                foreach ($request->lines as $line) {
                    $lineQuantity = $line['quantity'] ?? 0;
                    $lineUnitPrice = $line['unit_price'] ?? 0;
                    $lineSubtotal = $lineQuantity * $lineUnitPrice;
                    $baseCurrencyUnitPrice = $lineUnitPrice * $exchangeRate;
                    $baseCurrencySubtotal = $lineSubtotal * $exchangeRate;
                    
                    $vendorQuotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $lineUnitPrice,
                        'uom_id' => $line['uom_id'],
                        'quantity' => $lineQuantity,
                        'delivery_date' => $line['delivery_date'] ?? null,
                        'subtotal' => $lineSubtotal,
                        'base_currency_unit_price' => $baseCurrencyUnitPrice,
                        'base_currency_subtotal' => $baseCurrencySubtotal
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Vendor quotation updated successfully', [
                'quotation_id' => $vendorQuotation->quotation_id,
                'total_amount' => $totalAmount,
                'currency_code' => $vendorQuotation->currency_code
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor Quotation updated successfully',
                'data' => $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update vendor quotation', [
                'quotation_id' => $vendorQuotation->quotation_id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Vendor Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(VendorQuotation $vendorQuotation)
    {
        // Check if quotation can be deleted (only received status)
        if ($vendorQuotation->status !== 'received') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only quotations with status "received" can be deleted'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            $quotationId = $vendorQuotation->quotation_id;
            
            // Delete lines first
            $vendorQuotation->lines()->delete();
            
            // Delete quotation
            $vendorQuotation->delete();
            
            DB::commit();
            
            Log::info('Vendor quotation deleted successfully', [
                'quotation_id' => $quotationId
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor Quotation deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete vendor quotation', [
                'quotation_id' => $vendorQuotation->quotation_id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Vendor Quotation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function updateStatus(Request $request, VendorQuotation $vendorQuotation)
    {
        $request->validate([
            'status' => 'required|in:received,accepted,rejected'
        ]);
        
        // Additional validations based on status transition
        $currentStatus = $vendorQuotation->status;
        $newStatus = $request->status;
        
        $validTransitions = [
            'received' => ['accepted', 'rejected'],
            'accepted' => ['rejected'],
            'rejected' => ['accepted']
        ];
        
        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return response()->json([
                'status' => 'error',
                'message' => "Status cannot be changed from {$currentStatus} to {$newStatus}"
            ], 400);
        }
        
        $vendorQuotation->update(['status' => $newStatus]);
        
        Log::info('Vendor quotation status updated', [
            'quotation_id' => $vendorQuotation->quotation_id,
            'old_status' => $currentStatus,
            'new_status' => $newStatus
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Quotation status updated successfully',
            'data' => $vendorQuotation
        ]);
    }

    /**
     * Convert quotation currency
     */
    public function convertCurrency(Request $request, VendorQuotation $vendorQuotation)
    {
        $request->validate([
            'currency_code' => 'required|string|size:3',
            'use_quotation_date' => 'boolean'
        ]);

        if ($vendorQuotation->currency_code === $request->currency_code) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quotation is already in the requested currency'
            ], 400);
        }

        try {
            DB::beginTransaction();
            
            $exchangeDate = $request->use_quotation_date ? 
                $vendorQuotation->quotation_date : 
                now()->format('Y-m-d');
            
            $newExchangeRate = $this->getExchangeRate(
                $request->currency_code, 
                config('app.base_currency'),
                $exchangeDate
            );
            
            $oldExchangeRate = $vendorQuotation->exchange_rate;
            
            // Calculate conversion factor
            $conversionFactor = $oldExchangeRate / $newExchangeRate;
            
            // Update quotation
            $vendorQuotation->update([
                'currency_code' => $request->currency_code,
                'exchange_rate' => $newExchangeRate,
                'total_amount' => $vendorQuotation->total_amount * $conversionFactor
            ]);
            
            // Update line items
            foreach ($vendorQuotation->lines as $line) {
                $line->update([
                    'unit_price' => $line->unit_price * $conversionFactor,
                    'subtotal' => $line->subtotal * $conversionFactor
                ]);
            }
            
            DB::commit();
            
            Log::info('Vendor quotation currency converted', [
                'quotation_id' => $vendorQuotation->quotation_id,
                'old_currency' => $vendorQuotation->currency_code,
                'new_currency' => $request->currency_code,
                'conversion_factor' => $conversionFactor
            ]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Quotation currency converted successfully',
                'data' => $vendorQuotation->fresh()->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to convert quotation currency', [
                'quotation_id' => $vendorQuotation->quotation_id,
                'target_currency' => $request->currency_code,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to convert quotation currency',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compare quotations in same currency
     */
    public function compareInCurrency(Request $request)
    {
        $request->validate([
            'rfq_id' => 'required|exists:request_for_quotations,rfq_id',
            'currency_code' => 'required|string|size:3',
            'exchange_date' => 'nullable|date'
        ]);

        $rfqId = $request->rfq_id;
        $displayCurrency = $request->currency_code;
        $exchangeDate = $request->exchange_date ?? now()->format('Y-m-d');
        
        $quotations = VendorQuotation::with(['vendor', 'lines.item'])
            ->where('rfq_id', $rfqId)
            ->where('status', 'received')
            ->get();

        $convertedQuotations = $quotations->map(function ($quotation) use ($displayCurrency, $exchangeDate) {
            $quotation->display_amounts = $this->convertQuotationAmounts(
                $quotation, 
                $displayCurrency, 
                $exchangeDate
            );
            return $quotation;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'quotations' => $convertedQuotations,
                'display_currency' => $displayCurrency,
                'exchange_date' => $exchangeDate
            ]
        ]);
    }

    /**
     * Get available currencies from quotations
     */
    public function getAvailableCurrencies()
    {
        $currencies = VendorQuotation::select('currency_code')
            ->distinct()
            ->whereNotNull('currency_code')
            ->pluck('currency_code');

        return response()->json([
            'status' => 'success',
            'data' => $currencies
        ]);
    }

    /**
     * Helper method to get exchange rate
     */
    private function getExchangeRate($fromCurrency, $toCurrency, $date)
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        
        $rate = CurrencyRate::getCurrentRate($fromCurrency, $toCurrency, $date);
        
        if (!$rate) {
            throw new \Exception("Exchange rate not found for {$fromCurrency} to {$toCurrency} on {$date}");
        }
        
        return $rate;
    }

    /**
     * Helper method to convert quotation amounts to display currency
     */
    private function convertQuotationAmounts($quotation, $displayCurrency, $exchangeDate)
    {
        if ($quotation->currency_code === $displayCurrency) {
            return [
                'total_amount' => $quotation->total_amount,
                'exchange_rate' => 1.0,
                'conversion_date' => $exchangeDate
            ];
        }

        try {
            // Convert via base currency
            $baseAmount = $quotation->base_currency_total;
            $displayRate = $this->getExchangeRate(config('app.base_currency'), $displayCurrency, $exchangeDate);
            $displayAmount = $baseAmount / $displayRate;

            return [
                'total_amount' => $displayAmount,
                'exchange_rate' => $displayRate,
                'conversion_date' => $exchangeDate,
                'original_currency' => $quotation->currency_code,
                'original_amount' => $quotation->total_amount
            ];
        } catch (\Exception $e) {
            return [
                'total_amount' => $quotation->total_amount,
                'exchange_rate' => 1.0,
                'conversion_date' => $exchangeDate,
                'error' => 'Currency conversion failed'
            ];
        }
    }
}
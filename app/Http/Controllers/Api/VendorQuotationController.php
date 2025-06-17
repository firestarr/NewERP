<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\VendorQuotation;
use App\Models\RequestForQuotation;
use App\Models\CurrencyRate;
use App\Http\Requests\VendorQuotationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function store(VendorQuotationRequest $request)
    {
        // Check if RFQ exists and is in sent status
        $rfq = RequestForQuotation::findOrFail($request->rfq_id);
        if ($rfq->status !== 'sent') {
            return response()->json([
                'status' => 'error',
                'message' => 'Vendor quotations can only be created for RFQs in sent status'
            ], 400);
        }
        
        // Check if vendor quotation already exists for this vendor and RFQ
        $exists = VendorQuotation::where('rfq_id', $request->rfq_id)
                                 ->where('vendor_id', $request->vendor_id)
                                 ->exists();
        
        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Vendor quotation already exists for this RFQ and vendor'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Get exchange rate if not provided
            $exchangeRate = $request->exchange_rate;
            if (!$exchangeRate && $request->currency_code !== config('app.base_currency')) {
                $exchangeRate = $this->getExchangeRate(
                    $request->currency_code, 
                    config('app.base_currency'), 
                    $request->quotation_date
                );
            } else if ($request->currency_code === config('app.base_currency')) {
                $exchangeRate = 1.0;
            }
            
            // Create vendor quotation
            $vendorQuotation = VendorQuotation::create([
                'rfq_id' => $request->rfq_id,
                'vendor_id' => $request->vendor_id,
                'quotation_date' => $request->quotation_date,
                'validity_date' => $request->validity_date,
                'currency_code' => $request->currency_code ?? config('app.base_currency'),
                'exchange_rate' => $exchangeRate,
                'status' => 'received',
                'notes' => $request->notes,
                'payment_terms' => $request->payment_terms,
                'delivery_terms' => $request->delivery_terms,
                'total_amount' => $request->total_amount ?? 0,
                'base_currency_total' => ($request->total_amount ?? 0) * $exchangeRate
            ]);
            
            // Create quotation lines
            if ($request->has('lines')) {
                foreach ($request->lines as $line) {
                    $lineSubtotal = $line['quantity'] * $line['unit_price'];
                    $baseCurrencyUnitPrice = $line['unit_price'] * $exchangeRate;
                    $baseCurrencySubtotal = $lineSubtotal * $exchangeRate;
                    
                    $vendorQuotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $line['unit_price'],
                        'uom_id' => $line['uom_id'],
                        'quantity' => $line['quantity'],
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
                'message' => 'Vendor Quotation created successfully',
                'data' => $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
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
                'message' => 'Only received quotations can be updated'
            ], 400);
        }
        
        try {
            DB::beginTransaction();
            
            // Recalculate exchange rate if currency changed
            $exchangeRate = $request->exchange_rate ?? $vendorQuotation->exchange_rate;
            if ($request->has('currency_code') && $request->currency_code !== $vendorQuotation->currency_code) {
                $exchangeRate = $this->getExchangeRate(
                    $request->currency_code, 
                    config('app.base_currency'), 
                    $request->quotation_date ?? $vendorQuotation->quotation_date
                );
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
                'total_amount' => $request->total_amount ?? $vendorQuotation->total_amount,
                'base_currency_total' => ($request->total_amount ?? $vendorQuotation->total_amount) * $exchangeRate
            ]);
            
            // Update quotation lines if provided
            if ($request->has('lines')) {
                // Delete existing lines
                $vendorQuotation->lines()->delete();
                
                // Create new lines
                foreach ($request->lines as $line) {
                    $lineSubtotal = $line['quantity'] * $line['unit_price'];
                    $baseCurrencyUnitPrice = $line['unit_price'] * $exchangeRate;
                    $baseCurrencySubtotal = $lineSubtotal * $exchangeRate;
                    
                    $vendorQuotation->lines()->create([
                        'item_id' => $line['item_id'],
                        'unit_price' => $line['unit_price'],
                        'uom_id' => $line['uom_id'],
                        'quantity' => $line['quantity'],
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
                'message' => 'Vendor Quotation updated successfully',
                'data' => $vendorQuotation->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
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
                'message' => 'Only received quotations can be deleted'
            ], 400);
        }
        
        $vendorQuotation->lines()->delete();
        $vendorQuotation->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Vendor Quotation deleted successfully'
        ]);
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
            
            return response()->json([
                'status' => 'success',
                'message' => 'Quotation currency converted successfully',
                'data' => $vendorQuotation->fresh()->load(['vendor', 'requestForQuotation', 'lines.item', 'lines.unitOfMeasure'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
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
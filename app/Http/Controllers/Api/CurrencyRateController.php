<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyRateController extends Controller
{
    /**
     * Display a listing of exchange rates.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = CurrencyRate::query();
        
        // Filter by currency
        if ($request->has('from_currency')) {
            $query->where('from_currency', $request->from_currency);
        }
        
        if ($request->has('to_currency')) {
            $query->where('to_currency', $request->to_currency);
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        // Filter by date
        if ($request->has('effective_date')) {
            $query->where('effective_date', '<=', $request->effective_date)
                  ->where(function($q) use ($request) {
                      $q->where('end_date', '>=', $request->effective_date)
                        ->orWhereNull('end_date');
                  });
        }
        
        $rates = $query->orderBy('from_currency')
                     ->orderBy('to_currency')
                     ->orderBy('effective_date', 'desc')
                     ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $rates
        ]);
    }

    /**
     * Store a newly created exchange rate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'rate' => 'required|numeric|gt:0',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        // Check if there's an overlapping rate for the same currency pair
        $overlapping = CurrencyRate::where('from_currency', $request->from_currency)
            ->where('to_currency', $request->to_currency)
            ->where(function($query) use ($request) {
                $query->whereBetween('effective_date', [
                        $request->effective_date, 
                        $request->end_date ?? '9999-12-31'
                    ])
                    ->orWhereBetween('end_date', [
                        $request->effective_date, 
                        $request->end_date ?? '9999-12-31'
                    ])
                    ->orWhere(function($q) use ($request) {
                        $q->where('effective_date', '<=', $request->effective_date)
                          ->where(function($q2) use ($request) {
                              $q2->where('end_date', '>=', $request->end_date ?? '9999-12-31')
                                ->orWhereNull('end_date');
                          });
                    });
            })
            ->first();
            
        if ($overlapping) {
            return response()->json([
                'status' => 'error',
                'message' => 'There is already a rate defined for this currency pair during the specified period'
            ], 422);
        }

        $rate = CurrencyRate::create([
            'from_currency' => $request->from_currency,
            'to_currency' => $request->to_currency,
            'rate' => $request->rate,
            'effective_date' => $request->effective_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active ?? true
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Currency rate created successfully',
            'data' => $rate
        ], 201);
    }

    /**
     * Display the specified exchange rate.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $rate = CurrencyRate::find($id);
        
        if (!$rate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency rate not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $rate
        ]);
    }

    /**
     * Update the specified exchange rate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rate = CurrencyRate::find($id);
        
        if (!$rate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency rate not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rate' => 'required|numeric|gt:0',
            'effective_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for overlapping rates only if dates are changing
        if ($request->has('effective_date') || $request->has('end_date')) {
            $effectiveDate = $request->effective_date ?? $rate->effective_date;
            $endDate = $request->end_date ?? $rate->end_date ?? '9999-12-31';
            
            $overlapping = CurrencyRate::where('from_currency', $rate->from_currency)
                ->where('to_currency', $rate->to_currency)
                ->where('rate_id', '!=', $id)
                ->where(function($query) use ($effectiveDate, $endDate) {
                    $query->whereBetween('effective_date', [$effectiveDate, $endDate])
                        ->orWhereBetween('end_date', [$effectiveDate, $endDate])
                        ->orWhere(function($q) use ($effectiveDate, $endDate) {
                            $q->where('effective_date', '<=', $effectiveDate)
                              ->where(function($q2) use ($endDate) {
                                  $q2->where('end_date', '>=', $endDate)
                                    ->orWhereNull('end_date');
                              });
                        });
                })
                ->first();
                
            if ($overlapping) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'There is already a rate defined for this currency pair during the specified period'
                ], 422);
            }
        }

        $rate->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Currency rate updated successfully',
            'data' => $rate
        ]);
    }

    /**
     * Remove the specified exchange rate.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $rate = CurrencyRate::find($id);
        
        if (!$rate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency rate not found'
            ], 404);
        }

        $rate->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Currency rate deleted successfully'
        ]);
    }
    
    /**
     * ENHANCED METHOD: Get current rate with bidirectional info
     */
    public function getCurrentRate(Request $request)
    {
        // Map 'from' and 'to' to 'from_currency' and 'to_currency' for backward compatibility
        $input = $request->all();
        if (!isset($input['from_currency']) && isset($input['from'])) {
            $input['from_currency'] = $input['from'];
        }
        if (!isset($input['to_currency']) && isset($input['to'])) {
            $input['to_currency'] = $input['to'];
        }
        $request->merge($input);

        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $date = $request->date ?? now()->format('Y-m-d');
        
        $rate = CurrencyRate::findBidirectionalRate(
            $request->from_currency,
            $request->to_currency,
            $date
        );

        if (!$rate) {
            return response()->json([
                'status' => 'error',
                'message' => "No exchange rate found for {$request->from_currency} to {$request->to_currency}"
            ], 404);
        }

        // Calculate display rate based on direction
        $displayRate = $request->from_currency === $rate->from_currency 
            ? (float) $rate->rate 
            : round(1 / $rate->rate, 6);

        return response()->json([
            'status' => 'success',
            'data' => [
                'rate' => $displayRate,
                'from_currency' => $request->from_currency,
                'to_currency' => $request->to_currency,
                'date' => $rate->effective_date,
                'direction' => $request->from_currency === $rate->from_currency ? 'direct' : 'reverse',
                'base_rate_info' => [
                    'from_currency' => $rate->from_currency,
                    'to_currency' => $rate->to_currency,
                    'rate' => (float) $rate->rate,
                    'rate_id' => $rate->rate_id
                ]
            ]
        ]);
    }
    /**
     * NEW METHOD: Convert currency bidirectionally
     */
    public function convert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'amount' => 'required|numeric|min:0|max:999999999',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $rate = CurrencyRate::findBidirectionalRate(
            $request->from_currency,
            $request->to_currency,
            $request->date
        );

        if (!$rate) {
            return response()->json([
                'status' => 'error',
                'message' => "No exchange rate found for {$request->from_currency} to {$request->to_currency}"
            ], 404);
        }

        $conversion = $rate->getBidirectionalConversion(
            $request->from_currency,
            $request->amount
        );

        return response()->json([
            'status' => 'success',
            'data' => $conversion
        ]);
    }

    

    /**
     * NEW METHOD: Get available currency pairs
     */
    public function getAvailablePairs(Request $request)
    {
        $includeInactive = $request->boolean('include_inactive', false);
        
        $query = CurrencyRate::select('from_currency', 'to_currency', 'rate', 'effective_date', 'rate_id')
                             ->distinct();
        
        if (!$includeInactive) {
            $query->where('is_active', true);
        }
        
        $pairs = $query->orderBy('from_currency')->orderBy('to_currency')->get();
        
        $bidirectionalPairs = [];
        foreach ($pairs as $pair) {
            $bidirectionalPairs[] = [
                'pair_code' => $pair->from_currency . '/' . $pair->to_currency,
                'direct' => $pair->from_currency . ' → ' . $pair->to_currency,
                'reverse' => $pair->to_currency . ' → ' . $pair->from_currency,
                'currencies' => [$pair->from_currency, $pair->to_currency],
                'rate' => (float) $pair->rate,
                'rate_id' => $pair->rate_id,
                'effective_date' => $pair->effective_date
            ];
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $bidirectionalPairs
        ]);
    }
}
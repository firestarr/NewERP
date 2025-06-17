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
     * Get current exchange rate for a currency pair.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCurrentRate(Request $request)
    {
        // Map 'from' and 'to' to 'from_currency' and 'to_currency' if present
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
            'to_currency' => 'required|string|size:3',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        $date = $request->date ?? now()->format('Y-m-d');
        
        $rate = CurrencyRate::where('from_currency', $request->from_currency)
            ->where('to_currency', $request->to_currency)
            ->where('is_active', true)
            ->where('effective_date', '<=', $date)
            ->where(function($query) use ($date) {
                $query->where('end_date', '>=', $date)
                      ->orWhereNull('end_date');
            })
            ->orderBy('effective_date', 'desc')
            ->first();
            
        if (!$rate) {
            // Try to find a reverse rate
            $reverseRate = CurrencyRate::where('from_currency', $request->to_currency)
                ->where('to_currency', $request->from_currency)
                ->where('is_active', true)
                ->where('effective_date', '<=', $date)
                ->where(function($query) use ($date) {
                    $query->where('end_date', '>=', $date)
                          ->orWhereNull('end_date');
                })
                ->orderBy('effective_date', 'desc')
                ->first();
                
            if ($reverseRate) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'from_currency' => $request->from_currency,
                        'to_currency' => $request->to_currency,
                        'rate' => 1 / $reverseRate->rate,
                        'date' => $date,
                        'is_reversed' => true
                    ]
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'No exchange rate found for the specified currencies and date'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'from_currency' => $rate->from_currency,
                'to_currency' => $rate->to_currency,
                'rate' => $rate->rate,
                'date' => $date,
                'is_reversed' => false
            ]
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyRateService;
use App\Models\CurrencyRate;
use App\Models\SystemCurrency;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CurrencyRateController extends Controller
{
    protected $currencyRateService;

    public function __construct(CurrencyRateService $currencyRateService)
    {
        $this->currencyRateService = $currencyRateService;
    }

    /**
     * Store a new currency rate
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3|different:from_currency',
            'rate' => 'required|numeric|min:0.000001|max:999999999',
            'effective_date' => 'required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'source' => 'required|string|max:50',
            'calculation_method' => 'required|in:direct,inverse,cross,manual',
            'confidence_level' => 'required|in:high,medium,low',
            'is_active' => 'boolean',
            'is_bidirectional' => 'boolean',
            'created_by' => 'nullable|string|max:100',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $fromCurrency = strtoupper($request->from_currency);
            $toCurrency = strtoupper($request->to_currency);

            // Validate currencies exist
            $validCurrencies = SystemCurrency::whereIn('code', [$fromCurrency, $toCurrency])
                ->where('is_active', true)
                ->pluck('code')
                ->toArray();

            if (count($validCurrencies) !== 2) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'One or both currencies are not valid or active'
                ], 422);
            }

            // Check for existing active rate on the same date
            $existingRate = CurrencyRate::where('from_currency', $fromCurrency)
                ->where('to_currency', $toCurrency)
                ->where('effective_date', $request->effective_date)
                ->where('is_active', true)
                ->first();

            if ($existingRate) {
                return response()->json([
                    'status' => 'error',
                    'message' => "An active rate already exists for {$fromCurrency} to {$toCurrency} on this date"
                ], 409);
            }

            // Create the rate
            $currencyRate = CurrencyRate::create([
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'rate' => $request->rate,
                'effective_date' => $request->effective_date,
                'end_date' => $request->end_date,
                'source' => $request->source,
                'calculation_method' => $request->calculation_method,
                'confidence_level' => $request->confidence_level,
                'is_active' => $request->boolean('is_active', true),
                'is_bidirectional' => $request->boolean('is_bidirectional', false),
                'created_by' => $request->created_by ?? auth()->user()?->name ?? 'system',
                'metadata' => $request->metadata ?? []
            ]);

            // If bidirectional, create reverse rate
            if ($request->boolean('is_bidirectional', false)) {
                $reverseRate = 1 / $request->rate;
                
                CurrencyRate::create([
                    'from_currency' => $toCurrency,
                    'to_currency' => $fromCurrency,
                    'rate' => $reverseRate,
                    'effective_date' => $request->effective_date,
                    'end_date' => $request->end_date,
                    'source' => $request->source,
                    'calculation_method' => 'inverse',
                    'confidence_level' => $request->confidence_level,
                    'is_active' => $request->boolean('is_active', true),
                    'is_bidirectional' => true,
                    'created_by' => $request->created_by ?? auth()->user()?->name ?? 'system',
                    'metadata' => array_merge($request->metadata ?? [], [
                        'parent_rate_id' => $currencyRate->rate_id,
                        'auto_generated' => true
                    ])
                ]);
            }

            // Clear relevant cache entries
            $this->currencyRateService->clearExpiredCache();

            DB::commit();

            // Reload with relationships for response
            $currencyRate->load(['fromCurrencyInfo', 'toCurrencyInfo']);

            return response()->json([
                'status' => 'success',
                'message' => 'Currency rate created successfully',
                'data' => $currencyRate
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error creating currency rate', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while creating the currency rate'
            ], 500);
        }
    }

    /**
     * Update an existing currency rate
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rate' => 'sometimes|required|numeric|min:0.000001|max:999999999',
            'effective_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after:effective_date',
            'source' => 'sometimes|required|string|max:50',
            'calculation_method' => 'sometimes|required|in:direct,inverse,cross,manual',
            'confidence_level' => 'sometimes|required|in:high,medium,low',
            'is_active' => 'boolean',
            'updated_by' => 'nullable|string|max:100',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $currencyRate = CurrencyRate::findOrFail($id);
            $originalRate = $currencyRate->rate;
            $wasActive = $currencyRate->is_active;

            // Store original values for audit
            $originalData = $currencyRate->toArray();

            // Check for date conflicts if effective_date is being changed
            if ($request->has('effective_date') && $request->effective_date !== $currencyRate->effective_date) {
                $conflictingRate = CurrencyRate::where('from_currency', $currencyRate->from_currency)
                    ->where('to_currency', $currencyRate->to_currency)
                    ->where('effective_date', $request->effective_date)
                    ->where('is_active', true)
                    ->where('rate_id', '!=', $id)
                    ->first();

                if ($conflictingRate) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Another active rate already exists for this currency pair on {$request->effective_date}"
                    ], 409);
                }
            }

            // Update fields
            $updateData = array_filter([
                'rate' => $request->rate,
                'effective_date' => $request->effective_date,
                'end_date' => $request->end_date,
                'source' => $request->source,
                'calculation_method' => $request->calculation_method,
                'confidence_level' => $request->confidence_level,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
                'updated_by' => $request->updated_by ?? auth()->user()?->name ?? 'system',
                'metadata' => $request->metadata
            ], function($value) {
                return $value !== null;
            });

            $currencyRate->update($updateData);

            // Handle bidirectional updates
            if ($currencyRate->is_bidirectional && $request->has('rate')) {
                $reverseRate = CurrencyRate::where('from_currency', $currencyRate->to_currency)
                    ->where('to_currency', $currencyRate->from_currency)
                    ->where('effective_date', $currencyRate->effective_date)
                    ->where('metadata->parent_rate_id', $currencyRate->rate_id)
                    ->first();

                if ($reverseRate) {
                    $newReverseRate = 1 / $request->rate;
                    $reverseRate->update([
                        'rate' => $newReverseRate,
                        'updated_by' => $updateData['updated_by'],
                        'metadata' => array_merge($reverseRate->metadata ?? [], [
                            'last_sync' => now()->toISOString()
                        ])
                    ]);
                }
            }

            // Create audit trail entry
            $changes = [];
            foreach ($updateData as $key => $newValue) {
                if (isset($originalData[$key]) && $originalData[$key] != $newValue) {
                    $changes[$key] = [
                        'old' => $originalData[$key],
                        'new' => $newValue
                    ];
                }
            }

            if (!empty($changes)) {
                // Log significant changes
                Log::info('Currency rate updated', [
                    'rate_id' => $currencyRate->rate_id,
                    'currency_pair' => "{$currencyRate->from_currency}/{$currencyRate->to_currency}",
                    'changes' => $changes,
                    'updated_by' => $updateData['updated_by']
                ]);
            }

            // Clear relevant cache entries
            $this->currencyRateService->clearCacheForPair(
                $currencyRate->from_currency, 
                $currencyRate->to_currency
            );

            DB::commit();

            // Reload with relationships for response
            $currencyRate->load(['fromCurrencyInfo', 'toCurrencyInfo']);

            return response()->json([
                'status' => 'success',
                'message' => 'Currency rate updated successfully',
                'data' => $currencyRate,
                'changes' => $changes
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency rate not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error updating currency rate', [
                'rate_id' => $id,
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while updating the currency rate'
            ], 500);
        }
    }

    /**
     * Show a specific currency rate
     */
    public function show($id): JsonResponse
    {
        try {
            $currencyRate = CurrencyRate::with(['fromCurrencyInfo', 'toCurrencyInfo'])
                ->findOrFail($id);

            // Check for reverse rate if bidirectional
            $reverseRate = null;
            if ($currencyRate->is_bidirectional) {
                $reverseRate = CurrencyRate::where('from_currency', $currencyRate->to_currency)
                    ->where('to_currency', $currencyRate->from_currency)
                    ->where('effective_date', $currencyRate->effective_date)
                    ->first();
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'rate' => $currencyRate,
                    'reverse_rate' => $reverseRate,
                    'metadata' => [
                        'has_reverse' => $reverseRate !== null,
                        'is_current' => $currencyRate->effective_date <= now() && 
                                      ($currencyRate->end_date === null || $currencyRate->end_date > now()),
                        'days_active' => $currencyRate->effective_date->diffInDays(
                            $currencyRate->end_date ?? now()
                        )
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency rate not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error fetching currency rate', [
                'rate_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching the currency rate'
            ], 500);
        }
    }

    /**
     * Delete a currency rate
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $currencyRate = CurrencyRate::findOrFail($id);

            // Check if rate is being used in any transactions
            // Add your business logic here to check dependencies

            // Soft delete or deactivate instead of hard delete
            $currencyRate->update([
                'is_active' => false,
                'end_date' => now(),
                'updated_by' => auth()->user()?->name ?? 'system',
                'metadata' => array_merge($currencyRate->metadata ?? [], [
                    'deleted_at' => now()->toISOString(),
                    'deleted_by' => auth()->user()?->name ?? 'system'
                ])
            ]);

            // Handle bidirectional deletion
            if ($currencyRate->is_bidirectional) {
                $reverseRate = CurrencyRate::where('from_currency', $currencyRate->to_currency)
                    ->where('to_currency', $currencyRate->from_currency)
                    ->where('effective_date', $currencyRate->effective_date)
                    ->where('metadata->parent_rate_id', $currencyRate->rate_id)
                    ->first();

                if ($reverseRate) {
                    $reverseRate->update([
                        'is_active' => false,
                        'end_date' => now(),
                        'updated_by' => auth()->user()?->name ?? 'system'
                    ]);
                }
            }

            // Clear cache
            $this->currencyRateService->clearCacheForPair(
                $currencyRate->from_currency, 
                $currencyRate->to_currency
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Currency rate deactivated successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Currency rate not found'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error deleting currency rate', [
                'rate_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while deleting the currency rate'
            ], 500);
        }
    }

    /**
     * Get current rate (enhanced with bidirectional support)
     * Maintains compatibility with existing frontend
     */
    public function getCurrentRate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'date' => 'nullable|date',
            'analyze' => 'nullable|boolean' // For debugging/analysis
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fromCurrency = strtoupper($request->from_currency);
            $toCurrency = strtoupper($request->to_currency);
            $date = $request->date;
            $analyze = $request->boolean('analyze', false);

            // If analysis is requested, return all available paths
            if ($analyze) {
                $analysis = $this->currencyRateService->analyzeRatePaths($fromCurrency, $toCurrency, $date);
                
                return response()->json([
                    'status' => 'success',
                    'data' => $analysis
                ]);
            }

            // Regular rate lookup
            $result = $this->currencyRateService->getBidirectionalRate($fromCurrency, $toCurrency, $date);

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'data' => $result
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => "No exchange rate found for {$fromCurrency} to {$toCurrency}",
                'suggested_action' => 'Please check if both currencies are supported or try a different date'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error in getCurrentRate', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching exchange rate'
            ], 500);
        }
    }

    /**
     * Convert amount using bidirectional rates
     */
    public function convertAmount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->currencyRateService->convertAmount(
                $request->amount,
                strtoupper($request->from_currency),
                strtoupper($request->to_currency),
                $request->date
            );

            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'data' => $result
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => "Cannot convert from {$request->from_currency} to {$request->to_currency}"
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error in convertAmount', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during currency conversion'
            ], 500);
        }
    }

    /**
     * Get multiple rates for a base currency
     */
    public function getMultipleRates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'base_currency' => 'required|string|size:3',
            'target_currencies' => 'required|array|min:1',
            'target_currencies.*' => 'string|size:3',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $baseCurrency = strtoupper($request->base_currency);
            $targetCurrencies = array_map('strtoupper', $request->target_currencies);
            
            $results = $this->currencyRateService->getMultipleRates(
                $baseCurrency,
                $targetCurrencies,
                $request->date
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'base_currency' => $baseCurrency,
                    'rates' => $results,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getMultipleRates', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching multiple rates'
            ], 500);
        }
    }

    /**
     * List all currency rates with filtering and pagination
     * Enhanced version of your existing endpoint
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CurrencyRate::with(['fromCurrencyInfo', 'toCurrencyInfo'])
                ->orderBy('effective_date', 'desc')
                ->orderBy('from_currency')
                ->orderBy('to_currency');

            // Apply filters
            if ($request->filled('from_currency')) {
                $query->where('from_currency', strtoupper($request->from_currency));
            }

            if ($request->filled('to_currency')) {
                $query->where('to_currency', strtoupper($request->to_currency));
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            if ($request->filled('effective_date')) {
                $query->whereDate('effective_date', $request->effective_date);
            }

            if ($request->filled('calculation_method')) {
                $query->where('calculation_method', $request->calculation_method);
            }

            if ($request->filled('confidence_level')) {
                $query->where('confidence_level', $request->confidence_level);
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100);
            $rates = $query->paginate($perPage);

            // Add bidirectional information
            $ratesData = $rates->getCollection()->map(function ($rate) {
                $rateArray = $rate->toArray();
                
                // Check if reverse rate exists
                $reverseExists = CurrencyRate::where('from_currency', $rate->to_currency)
                    ->where('to_currency', $rate->from_currency)
                    ->where('is_active', true)
                    ->exists();
                
                $rateArray['has_reverse_rate'] = $reverseExists;
                $rateArray['is_truly_bidirectional'] = $rate->is_bidirectional && $reverseExists;
                
                return $rateArray;
            });

            return response()->json([
                'status' => 'success',
                'data' => $ratesData,
                'meta' => [
                    'current_page' => $rates->currentPage(),
                    'last_page' => $rates->lastPage(),
                    'per_page' => $rates->perPage(),
                    'total' => $rates->total(),
                    'from' => $rates->firstItem(),
                    'to' => $rates->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in index', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching currency rates'
            ], 500);
        }
    }

    /**
     * Get available currencies
     */
    public function getCurrencies(Request $request): JsonResponse
    {
        try {
            $query = SystemCurrency::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('code');

            if ($request->boolean('with_rates_only', false)) {
                // Only return currencies that have rates defined
                $query->whereExists(function ($subquery) {
                    $subquery->select('rate_id')
                        ->from('currency_rates')
                        ->whereColumn('currency_rates.from_currency', 'system_currencies.code')
                        ->orWhereColumn('currency_rates.to_currency', 'system_currencies.code')
                        ->where('currency_rates.is_active', true);
                });
            }

            $currencies = $query->get(['code', 'name', 'symbol', 'decimal_places']);

            return response()->json([
                'status' => 'success',
                'data' => $currencies
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getCurrencies', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching currencies'
            ], 500);
        }
    }

    /**
     * Get rate analysis and statistics
     */
    public function getRateAnalysis(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|size:3',
            'to_currency' => 'required|string|size:3',
            'date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $analysis = $this->currencyRateService->analyzeRatePaths(
                strtoupper($request->from_currency),
                strtoupper($request->to_currency),
                $request->date
            );

            return response()->json([
                'status' => 'success',
                'data' => $analysis
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getRateAnalysis', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during rate analysis'
            ], 500);
        }
    }

    /**
     * Get cache statistics (admin endpoint)
     */
    public function getCacheStats(): JsonResponse
    {
        try {
            $stats = $this->currencyRateService->getCacheStats();
            
            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getCacheStats', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching cache statistics'
            ], 500);
        }
    }

    /**
     * Clear expired cache entries (admin endpoint)
     */
    public function clearExpiredCache(): JsonResponse
    {
        try {
            $cleared = $this->currencyRateService->clearExpiredCache();
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'entries_cleared' => $cleared,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in clearExpiredCache', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while clearing cache'
            ], 500);
        }
    }

    /**
     * Health check for currency rate system
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $baseCurrency = config('app.base_currency', 'USD');
            $testCurrencies = ['EUR', 'GBP', 'JPY'];
            $results = [];

            foreach ($testCurrencies as $currency) {
                $rate = $this->currencyRateService->getBidirectionalRate($baseCurrency, $currency);
                $results[$currency] = [
                    'available' => $rate !== null,
                    'method' => $rate['direction'] ?? null
                ];
            }

            $cacheStats = $this->currencyRateService->getCacheStats();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'system_healthy' => true,
                    'base_currency' => $baseCurrency,
                    'test_rates' => $results,
                    'cache_stats' => $cacheStats,
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in healthCheck', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Currency rate system health check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
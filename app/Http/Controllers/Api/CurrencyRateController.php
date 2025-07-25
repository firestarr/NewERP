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

class CurrencyRateController extends Controller
{
    protected $currencyRateService;

    public function __construct(CurrencyRateService $currencyRateService)
    {
        $this->currencyRateService = $currencyRateService;
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
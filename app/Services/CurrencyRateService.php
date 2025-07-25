<?php

namespace App\Services;

use App\Models\CurrencyRate;
use App\Models\CurrencyRateCache;
use App\Models\CurrencySetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CurrencyRateService
{
    protected $baseCurrency;
    protected $bidirectionalEnabled;
    protected $crossCurrencyEnabled;
    protected $cacheEnabled;
    protected $cacheTtl;
    protected $maxCrossHops;
    protected $confidenceThreshold;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    /**
     * Load configuration from currency_settings
     */
    private function loadConfiguration(): void
    {
        $this->baseCurrency = config('app.base_currency', 'USD');
        $this->bidirectionalEnabled = $this->getSetting('bidirectional_enabled', true);
        $this->crossCurrencyEnabled = $this->getSetting('cross_currency_enabled', true);
        $this->cacheEnabled = $this->getSetting('rate_cache_enabled', true);
        $this->cacheTtl = $this->getSetting('rate_cache_ttl', 300);
        $this->maxCrossHops = $this->getSetting('max_cross_currency_hops', 2);
        $this->confidenceThreshold = $this->getSetting('default_confidence_threshold', 'medium');
    }

    /**
     * Get setting value with fallback
     */
    private function getSetting(string $key, $default = null)
    {
        try {
            $setting = DB::table('currency_settings')->where('key', $key)->first();
            if ($setting) {
                return $setting->type === 'boolean' ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : $setting->value;
            }
        } catch (\Exception $e) {
            Log::warning("Could not load currency setting: {$key}", ['error' => $e->getMessage()]);
        }
        
        return $default;
    }

    /**
     * Get bidirectional exchange rate (main method)
     */
    public function getBidirectionalRate(string $fromCurrency, string $toCurrency, ?string $date = null): ?array
    {
        $date = $date ? Carbon::parse($date) : now();
        $cacheKey = "currency_rate_{$fromCurrency}_{$toCurrency}_{$date->format('Y-m-d')}";

        // Same currency check
        if ($fromCurrency === $toCurrency) {
            return $this->createRateResult(1.0, $fromCurrency, $toCurrency, $date, 'same', 'high');
        }

        // Check cache first
        if ($this->cacheEnabled) {
            $cached = $this->getCachedRate($fromCurrency, $toCurrency, $date);
            if ($cached) {
                return $cached;
            }
        }

        $result = null;

        if ($this->bidirectionalEnabled) {
            // Try direct rate first
            $result = $this->getDirectRate($fromCurrency, $toCurrency, $date);
            
            // Try inverse rate if direct not found
            if (!$result) {
                $result = $this->getInverseRate($fromCurrency, $toCurrency, $date);
            }
            
            // Try cross-currency if enabled and other methods failed
            if (!$result && $this->crossCurrencyEnabled) {
                $result = $this->getCrossRate($fromCurrency, $toCurrency, $date);
            }
        } else {
            // Fallback to direct rate only
            $result = $this->getDirectRate($fromCurrency, $toCurrency, $date);
        }

        // Cache the result if found
        if ($result && $this->cacheEnabled) {
            $this->cacheRate($result);
        }

        return $result;
    }

    /**
     * Get direct rate from database
     */
    private function getDirectRate(string $fromCurrency, string $toCurrency, Carbon $date): ?array
    {
        $rate = CurrencyRate::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('is_active', true)
            ->where('effective_date', '<=', $date->format('Y-m-d'))
            ->where(function($query) use ($date) {
                $query->where('end_date', '>=', $date->format('Y-m-d'))
                    ->orWhereNull('end_date');
            })
            ->orderBy('effective_date', 'desc')
            ->first();

        if ($rate) {
            return $this->createRateResult(
                $rate->rate,
                $fromCurrency,
                $toCurrency,
                Carbon::parse($rate->effective_date),
                'direct',
                $rate->confidence_level ?? 'high',
                $rate->rate_id
            );
        }

        return null;
    }

    /**
     * Get inverse rate calculation
     */
    private function getInverseRate(string $fromCurrency, string $toCurrency, Carbon $date): ?array
    {
        $rate = CurrencyRate::where('from_currency', $toCurrency)
            ->where('to_currency', $fromCurrency)
            ->where('is_active', true)
            ->where('effective_date', '<=', $date->format('Y-m-d'))
            ->where(function($query) use ($date) {
                $query->where('end_date', '>=', $date->format('Y-m-d'))
                    ->orWhereNull('end_date');
            })
            ->orderBy('effective_date', 'desc')
            ->first();

        if ($rate && $rate->rate > 0) {
            $precision = $this->getSetting('inverse_rate_precision', 6);
            
            return $this->createRateResult(
                round(1 / $rate->rate, $precision),
                $fromCurrency,
                $toCurrency,
                Carbon::parse($rate->effective_date),
                'inverse',
                $this->adjustConfidenceForInverse($rate->confidence_level ?? 'high'),
                $rate->rate_id
            );
        }

        return null;
    }

    /**
     * Get cross-currency rate via base currency
     */
    private function getCrossRate(string $fromCurrency, string $toCurrency, Carbon $date, int $hops = 0): ?array
    {
        if ($hops >= $this->maxCrossHops) {
            return null;
        }

        // Skip if either currency is already the base currency
        if ($fromCurrency === $this->baseCurrency || $toCurrency === $this->baseCurrency) {
            return null;
        }

        // Get rate from source currency to base currency
        $fromToBase = $this->getBidirectionalRate($fromCurrency, $this->baseCurrency, $date->format('Y-m-d'));
        if (!$fromToBase) {
            return null;
        }

        // Get rate from base currency to target currency
        $baseToTarget = $this->getBidirectionalRate($this->baseCurrency, $toCurrency, $date->format('Y-m-d'));
        if (!$baseToTarget) {
            return null;
        }

        $crossRate = $fromToBase['rate'] * $baseToTarget['rate'];
        $confidence = $this->calculateCrossConfidence($fromToBase['confidence'], $baseToTarget['confidence']);

        return $this->createRateResult(
            $crossRate,
            $fromCurrency,
            $toCurrency,
            $date,
            'cross',
            $confidence,
            null,
            "{$fromCurrency} → {$this->baseCurrency} → {$toCurrency}"
        );
    }

    /**
     * Create standardized rate result array
     */
    private function createRateResult(
        float $rate,
        string $fromCurrency,
        string $toCurrency,
        Carbon $date,
        string $direction,
        string $confidence,
        ?int $sourceRateId = null,
        ?string $calculationPath = null
    ): array {
        return [
            'rate' => $rate,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'date' => $date->format('Y-m-d'),
            'direction' => $direction,
            'confidence' => $confidence,
            'source_rate_id' => $sourceRateId,
            'calculation_path' => $calculationPath,
            'timestamp' => now()->toISOString()
        ];
    }

    /**
     * Convert amount with bidirectional support
     */
    public function convertAmount(float $amount, string $fromCurrency, string $toCurrency, ?string $date = null): ?array
    {
        $rateInfo = $this->getBidirectionalRate($fromCurrency, $toCurrency, $date);

        if (!$rateInfo) {
            return null;
        }

        $convertedAmount = $amount * $rateInfo['rate'];

        return [
            ...$rateInfo,
            'original_amount' => $amount,
            'converted_amount' => $convertedAmount,
            'formatted_original' => $this->formatCurrency($amount, $fromCurrency),
            'formatted_converted' => $this->formatCurrency($convertedAmount, $toCurrency)
        ];
    }

    /**
     * Get multiple rates for a base currency
     */
    public function getMultipleRates(string $baseCurrency, array $targetCurrencies, ?string $date = null): array
    {
        $results = [];
        
        foreach ($targetCurrencies as $target) {
            if ($target !== $baseCurrency) {
                $rate = $this->getBidirectionalRate($baseCurrency, $target, $date);
                if ($rate) {
                    $results[$target] = $rate;
                }
            }
        }

        return $results;
    }

    /**
     * Analyze available rate paths for debugging
     */
    public function analyzeRatePaths(string $fromCurrency, string $toCurrency, ?string $date = null): array
    {
        $date = $date ? Carbon::parse($date) : now();
        $paths = [];

        // Direct path
        $direct = $this->getDirectRate($fromCurrency, $toCurrency, $date);
        if ($direct) {
            $paths['direct'] = $direct;
        }

        // Inverse path
        $inverse = $this->getInverseRate($fromCurrency, $toCurrency, $date);
        if ($inverse) {
            $paths['inverse'] = $inverse;
        }

        // Cross-currency paths
        if ($this->crossCurrencyEnabled) {
            $cross = $this->getCrossRate($fromCurrency, $toCurrency, $date);
            if ($cross) {
                $paths['cross'] = $cross;
            }
        }

        return [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'date' => $date->format('Y-m-d'),
            'available_paths' => $paths,
            'recommended_path' => $this->selectBestPath($paths),
            'analysis_timestamp' => now()->toISOString()
        ];
    }

    /**
     * Cache management methods
     */
    private function getCachedRate(string $fromCurrency, string $toCurrency, Carbon $date): ?array
    {
        try {
            $cached = DB::table('currency_rate_cache')
                ->where('from_currency', $fromCurrency)
                ->where('to_currency', $toCurrency)
                ->where('cache_date', $date->format('Y-m-d'))
                ->where('expires_at', '>', now())
                ->first();

            if ($cached) {
                return [
                    'rate' => (float) $cached->rate,
                    'from_currency' => $cached->from_currency,
                    'to_currency' => $cached->to_currency,
                    'date' => $cached->cache_date,
                    'direction' => $cached->calculation_method,
                    'confidence' => $cached->confidence_level,
                    'source_rate_id' => $cached->source_rate_id,
                    'calculation_path' => $cached->calculation_path ? json_decode($cached->calculation_path, true) : null,
                    'cached' => true,
                    'cached_at' => $cached->cached_at
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Cache retrieval failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function cacheRate(array $rateData): void
    {
        try {
            DB::table('currency_rate_cache')->updateOrInsert(
                [
                    'from_currency' => $rateData['from_currency'],
                    'to_currency' => $rateData['to_currency'],
                    'cache_date' => $rateData['date']
                ],
                [
                    'rate' => $rateData['rate'],
                    'calculation_method' => $rateData['direction'],
                    'confidence_level' => $rateData['confidence'],
                    'calculation_path' => $rateData['calculation_path'] ? json_encode($rateData['calculation_path']) : null,
                    'source_rate_id' => $rateData['source_rate_id'],
                    'cached_at' => now(),
                    'expires_at' => now()->addSeconds($this->cacheTtl)
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Cache storage failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Helper methods
     */
    private function adjustConfidenceForInverse(string $originalConfidence): string
    {
        // Reduce confidence for inverse calculations
        $confidenceMap = [
            'high' => 'high',
            'medium' => 'medium',
            'low' => 'low'
        ];

        return $confidenceMap[$originalConfidence] ?? 'medium';
    }

    private function calculateCrossConfidence(string $confidence1, string $confidence2): string
    {
        $confidenceValues = ['low' => 1, 'medium' => 2, 'high' => 3];
        $reverseMap = [1 => 'low', 2 => 'medium', 3 => 'high'];

        $value1 = $confidenceValues[$confidence1] ?? 2;
        $value2 = $confidenceValues[$confidence2] ?? 2;

        // Take minimum confidence and reduce by one level for cross calculation
        $resultValue = max(1, min($value1, $value2) - 1);

        return $reverseMap[$resultValue];
    }

    private function selectBestPath(array $paths): ?array
    {
        if (empty($paths)) {
            return null;
        }

        // Priority: direct > inverse > cross
        $priority = ['direct', 'inverse', 'cross'];

        foreach ($priority as $method) {
            if (isset($paths[$method])) {
                return $paths[$method];
            }
        }

        return array_values($paths)[0]; // Fallback to first available
    }

    private function formatCurrency(float $amount, string $currency): string
    {
        // Get decimal places for currency from system_currencies table
        $decimals = 2; // Default
        
        try {
            $currencyInfo = DB::table('system_currencies')
                ->where('code', $currency)
                ->first();
                
            if ($currencyInfo) {
                $decimals = $currencyInfo->decimal_places ?? 2;
            }
        } catch (\Exception $e) {
            // Use default if query fails
        }

        return number_format($amount, $decimals);
    }

    /**
     * Clear expired cache entries
     */
    public function clearExpiredCache(): int
    {
        try {
            return DB::table('currency_rate_cache')
                ->where('expires_at', '<', now())
                ->delete();
        } catch (\Exception $e) {
            Log::error('Failed to clear expired cache', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $total = DB::table('currency_rate_cache')->count();
            $expired = DB::table('currency_rate_cache')->where('expires_at', '<', now())->count();
            
            return [
                'total_entries' => $total,
                'expired_entries' => $expired,
                'active_entries' => $total - $expired,
                'cache_hit_ratio' => $this->calculateCacheHitRatio()
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function calculateCacheHitRatio(): float
    {
        // This would require additional tracking in a real implementation
        return 0.0;
    }
}
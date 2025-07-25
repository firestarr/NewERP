<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $table = 'currency_rates';
    protected $primaryKey = 'rate_id';
    
    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'effective_date',
        'end_date',
        'is_active'
    ];
    
    protected $casts = [
        'rate' => 'float',
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean'
    ];

    public function fromCurrencyInfo()
    {
        return $this->belongsTo(SystemCurrency::class, 'from_currency', 'code');
    }

    public function toCurrencyInfo()
    {
        return $this->belongsTo(SystemCurrency::class, 'to_currency', 'code');
    }
    
    /**
     * Get the current rate for a currency pair.
     *
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param string|null $date
     * @return float|null
     */
    public static function getCurrentRate($fromCurrency, $toCurrency, $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        
        // Try to find direct rate
        $rate = self::where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->where('is_active', true)
            ->where('effective_date', '<=', $date)
            ->where(function($query) use ($date) {
                $query->where('end_date', '>=', $date)
                      ->orWhereNull('end_date');
            })
            ->orderBy('effective_date', 'desc')
            ->first();
            
        if ($rate) {
            return $rate->rate;
        }
        
        // Try to find reverse rate
        $reverseRate = self::where('from_currency', $toCurrency)
            ->where('to_currency', $fromCurrency)
            ->where('is_active', true)
            ->where('effective_date', '<=', $date)
            ->where(function($query) use ($date) {
                $query->where('end_date', '>=', $date)
                      ->orWhereNull('end_date');
            })
            ->orderBy('effective_date', 'desc')
            ->first();
            
        if ($reverseRate) {
            return 1 / $reverseRate->rate;
        }
        
        // Try to find a path through base currency if direct conversion not available
        $baseCurrency = config('app.base_currency', 'USD');
        
        // No direct conversion to base currency needed
        if ($fromCurrency === $baseCurrency || $toCurrency === $baseCurrency) {
            return null;
        }
        
        // Try to convert via base currency
        $fromToBase = self::getCurrentRate($fromCurrency, $baseCurrency, $date);
        $baseToTo = self::getCurrentRate($baseCurrency, $toCurrency, $date);
        
        if ($fromToBase && $baseToTo) {
            return $fromToBase * $baseToTo;
        }
        
        return null;
    }
}
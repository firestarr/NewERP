<?php
// app/Models/CurrencyRate.php - REPLACE COMPLETELY

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
        'is_active',
        'is_bidirectional_enabled',
        'conversion_notes',
        'rate_type'
    ];
    
    protected $casts = [
        'rate' => 'decimal:6',
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_bidirectional_enabled' => 'boolean'
    ];

    /**
     * BIDIRECTIONAL CONVERSION METHODS
     */
    
    public function convertTo(float $amount): float
    {
        return round($amount * $this->rate, 2);
    }

    public function convertFrom(float $amount): float
    {
        return round($amount / $this->rate, 2);
    }

    public function getBidirectionalConversion(string $fromCurrency, float $amount): array
    {
        if ($fromCurrency === $this->from_currency) {
            // Direct conversion
            $convertedAmount = $this->convertTo($amount);
            return [
                'from_currency' => $this->from_currency,
                'to_currency' => $this->to_currency,
                'amount' => $amount,
                'converted_amount' => $convertedAmount,
                'rate' => (float) $this->rate,
                'calculation' => "{$amount} × {$this->rate} = {$convertedAmount}",
                'direction' => 'direct',
                'rate_id' => $this->rate_id
            ];
        } else {
            // Reverse conversion
            $inverseRate = round(1 / $this->rate, 6);
            $convertedAmount = $this->convertFrom($amount);
            return [
                'from_currency' => $this->to_currency,
                'to_currency' => $this->from_currency,
                'amount' => $amount,
                'converted_amount' => $convertedAmount,
                'rate' => $inverseRate,
                'calculation' => "{$amount} ÷ {$this->rate} = {$convertedAmount}",
                'direction' => 'reverse',
                'rate_id' => $this->rate_id
            ];
        }
    }

    public static function findBidirectionalRate(string $fromCurrency, string $toCurrency, string $date = null): ?self
    {
        $date = $date ?? now()->format('Y-m-d');
        
        // Try direct rate first
        $rate = self::where('from_currency', $fromCurrency)
                   ->where('to_currency', $toCurrency)
                   ->where('is_active', true)
                   ->where('is_bidirectional_enabled', true)
                   ->where('effective_date', '<=', $date)
                   ->where(function($query) use ($date) {
                       $query->where('end_date', '>=', $date)->orWhereNull('end_date');
                   })
                   ->orderBy('effective_date', 'desc')
                   ->first();

        if ($rate) {
            return $rate;
        }

        // Try reverse rate
        return self::where('from_currency', $toCurrency)
                  ->where('to_currency', $fromCurrency)
                  ->where('is_active', true)
                  ->where('is_bidirectional_enabled', true)
                  ->where('effective_date', '<=', $date)
                  ->where(function($query) use ($date) {
                      $query->where('end_date', '>=', $date)->orWhereNull('end_date');
                  })
                  ->orderBy('effective_date', 'desc')
                  ->first();
    }

    // Keep existing getCurrentRate method for backward compatibility
    public static function getCurrentRate($fromCurrency, $toCurrency, $date = null)
    {
        $rate = self::findBidirectionalRate($fromCurrency, $toCurrency, $date);
        
        if (!$rate) {
            return null;
        }

        if ($fromCurrency === $rate->from_currency) {
            return (float) $rate->rate;
        } else {
            return round(1 / $rate->rate, 6);
        }
    }
}
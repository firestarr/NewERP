<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAdjustmentLine extends Model
{
    use HasFactory;

    protected $table = 'stock_adjustment_lines';
    protected $primaryKey = 'line_id';
    protected $fillable = [
        'adjustment_id', 
        'item_id', 
        'warehouse_id', 
        'book_quantity', 
        'adjusted_quantity', 
        'variance'
    ];

    protected $casts = [
        'book_quantity' => 'decimal:2',
        'adjusted_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    /**
     * Get the stock adjustment that owns this line
     */
    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class, 'adjustment_id', 'adjustment_id');
    }

    /**
     * Get the item for this adjustment line
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the warehouse for this adjustment line
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    /**
     * Calculate variance percentage
     */
    public function getVariancePercentageAttribute()
    {
        if ($this->book_quantity == 0) {
            return null; // Avoid division by zero
        }
        
        return round(($this->variance / $this->book_quantity) * 100, 2);
    }

    /**
     * Get variance type (increase/decrease/no_change)
     */
    public function getVarianceTypeAttribute()
    {
        if ($this->variance > 0) {
            return 'increase';
        } elseif ($this->variance < 0) {
            return 'decrease';
        } else {
            return 'no_change';
        }
    }

    /**
     * Get absolute variance value
     */
    public function getAbsVarianceAttribute()
    {
        return abs($this->variance);
    }

    /**
     * Get variance direction text
     */
    public function getVarianceDirectionAttribute()
    {
        if ($this->variance > 0) {
            return 'Increase';
        } elseif ($this->variance < 0) {
            return 'Decrease';
        } else {
            return 'No Change';
        }
    }

    /**
     * Get current stock for this item in this warehouse
     */
    public function getCurrentStockAttribute()
    {
        $itemStock = ItemStock::where('item_id', $this->item_id)
            ->where('warehouse_id', $this->warehouse_id)
            ->first();
        
        return $itemStock ? $itemStock->quantity : 0;
    }

    /**
     * Check if variance is significant (configurable threshold)
     */
    public function isSignificantVariance($threshold = 5)
    {
        return $this->variance_percentage !== null && abs($this->variance_percentage) >= $threshold;
    }

    /**
     * Get formatted variance text for display
     */
    public function getFormattedVarianceAttribute()
    {
        if ($this->variance == 0) {
            return 'No change';
        }
        
        $sign = $this->variance > 0 ? '+' : '';
        return $sign . number_format($this->variance, 2);
    }

    /**
     * Get CSS class for variance display
     */
    public function getVarianceCssClassAttribute()
    {
        if ($this->variance > 0) {
            return 'text-success'; // Green for increases
        } elseif ($this->variance < 0) {
            return 'text-danger'; // Red for decreases
        } else {
            return 'text-muted'; // Gray for no change
        }
    }

    /**
     * Boot method to automatically calculate variance
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($line) {
            $line->variance = $line->adjusted_quantity - $line->book_quantity;
        });
    }
}
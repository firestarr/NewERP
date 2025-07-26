<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Item;
use App\Models\UnitOfMeasure;
use App\Models\Sales\DeliveryLine;

class SOLine extends Model
{
    protected $table = 'SOLine';
    protected $primaryKey = 'line_id';
    public $timestamps = true;

    protected $fillable = [
        'so_id',
        'item_id',
        'unit_price',
        'quantity',
        'uom_id',
        'delivery_date',
        'discount',
        'tax',
        'subtotal',
        'total',
        'notes',
        'base_currency_unit_price',
        'base_currency_subtotal',
        'base_currency_discount',
        'base_currency_tax',
        'base_currency_total'
    ];

    protected $casts = [
        'unit_price' => 'decimal:5',
        'quantity' => 'decimal:4',
        'discount' => 'decimal:5',
        'tax' => 'decimal:5',
        'subtotal' => 'decimal:5',
        'total' => 'decimal:5',
        'base_currency_unit_price' => 'decimal:5',
        'base_currency_subtotal' => 'decimal:5',
        'base_currency_discount' => 'decimal:5',
        'base_currency_tax' => 'decimal:5',
        'base_currency_total' => 'decimal:5',
        'delivery_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the sales order that owns this line
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'so_id', 'so_id');
    }

    /**
     * Get the item for this line
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the unit of measure for this line
     */
    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'uom_id', 'uom_id');
    }

    /**
     * Get delivery lines for this sales order line
     */
    public function deliveryLines(): HasMany
    {
        return $this->hasMany(DeliveryLine::class, 'so_line_id', 'line_id');
    }

    /**
     * Calculate remaining quantity to be delivered
     */
    public function getRemainingQuantityAttribute()
    {
        $deliveredQuantity = $this->deliveryLines()->sum('quantity');
        return $this->quantity - $deliveredQuantity;
    }

    /**
     * Check if this line is fully delivered
     */
    public function getIsFullyDeliveredAttribute()
    {
        return $this->remaining_quantity <= 0;
    }

    /**
     * Get delivery status for this line
     */
    public function getDeliveryStatusAttribute()
    {
        $deliveredQuantity = $this->deliveryLines()->sum('quantity');

        if ($deliveredQuantity == 0) {
            return 'Not Delivered';
        } elseif ($deliveredQuantity >= $this->quantity) {
            return 'Fully Delivered';
        } else {
            return 'Partially Delivered';
        }
    }

    /**
     * Check if delivery date has passed
     */
    public function getIsOverdueAttribute()
    {
        if (!$this->delivery_date) {
            return false;
        }

        return $this->delivery_date->isPast() && !$this->is_fully_delivered;
    }

    /**
     * Get days until delivery or days overdue
     */
    public function getDaysToDeliveryAttribute()
    {
        if (!$this->delivery_date) {
            return null;
        }

        return now()->diffInDays($this->delivery_date, false);
    }

    /**
     * Scope for lines that are overdue
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotNull('delivery_date')
            ->where('delivery_date', '<', now())
            ->whereHas('deliveryLines', function ($q) {
                $q->havingRaw('SUM(quantity) < ?', [$this->quantity]);
            }, '<', 1);
    }

    /**
     * Scope for lines due today
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('delivery_date', today());
    }

    /**
     * Scope for lines due this week
     */
    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('delivery_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate subtotal and total when saving
        static::saving(function ($soline) {
            // Calculate subtotal
            $soline->subtotal = $soline->unit_price * $soline->quantity;

            // Calculate total (subtotal - discount + tax)
            $soline->total = $soline->subtotal - ($soline->discount ?? 0) + ($soline->tax ?? 0);
        });
    }
}

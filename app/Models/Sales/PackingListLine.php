<?php

namespace App\Models\Sales;

use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackingListLine extends Model
{
    use HasFactory;

    protected $table = 'PackingListLine';
    protected $primaryKey = 'line_id';
    public $timestamps = false;
    
    protected $fillable = [
        'packing_list_id',
        'delivery_line_id',
        'item_id',
        'packed_quantity',
        'warehouse_id',
        'batch_number',
        'package_number',
        'package_type',
        'weight_per_unit',
        'volume_per_unit',
        'notes'
    ];

    protected $casts = [
        'packed_quantity' => 'float',
        'weight_per_unit' => 'float',
        'volume_per_unit' => 'float',
    ];

    /**
     * Get the packing list that owns the packing list line.
     */
    public function packingList(): BelongsTo
    {
        return $this->belongsTo(PackingList::class, 'packing_list_id');
    }

    /**
     * Get the delivery line that owns the packing list line.
     */
    public function deliveryLine(): BelongsTo
    {
        return $this->belongsTo(DeliveryLine::class, 'delivery_line_id');
    }

    /**
     * Get the item that the packing list line belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Get the warehouse that the packing list line belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get total weight for this line
     */
    public function getTotalWeightAttribute()
    {
        return $this->packed_quantity * $this->weight_per_unit;
    }

    /**
     * Get total volume for this line
     */
    public function getTotalVolumeAttribute()
    {
        return $this->packed_quantity * $this->volume_per_unit;
    }

    /**
     * Check if this line is fully packed against delivery line
     */
    public function getIsFullyPackedAttribute()
    {
        if (!$this->deliveryLine) {
            return false;
        }
        
        $totalPackedForDeliveryLine = static::where('delivery_line_id', $this->delivery_line_id)
            ->sum('packed_quantity');
            
        return $totalPackedForDeliveryLine >= $this->deliveryLine->delivered_quantity;
    }
}
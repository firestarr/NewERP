<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackingList extends Model
{
    use HasFactory;

    protected $table = 'PackingList';
    protected $primaryKey = 'packing_list_id';
    public $timestamps = false;

    protected $fillable = [
        'packing_list_number',
        'packing_date',
        'delivery_id',
        'customer_id',
        'status',
        'packed_by',
        'checked_by',
        'total_weight',
        'total_volume',
        'number_of_packages',
        'notes'
    ];

    protected $casts = [
        'packing_date' => 'date',
        'total_weight' => 'float',
        'total_volume' => 'float',
        'number_of_packages' => 'integer',
    ];

    // Status constants
    const STATUS_DRAFT = 'Draft';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_SHIPPED = 'Shipped';

    /**
     * Get the delivery that owns the packing list.
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    /**
     * Get the customer that owns the packing list.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the packing list lines for the packing list.
     */
    public function packingListLines(): HasMany
    {
        return $this->hasMany(PackingListLine::class, 'packing_list_id');
    }

    /**
     * Generate packing list number
     */
    public static function generatePackingListNumber()
    {
        $year = date('y');
        $prefix = "PL-{$year}-";
        
        $lastPackingList = static::where('packing_list_number', 'like', $prefix.'%')
            ->orderBy('packing_list_number', 'desc')
            ->first();

        if ($lastPackingList) {
            $lastNumber = intval(substr($lastPackingList->packing_list_number, strlen($prefix)));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get total packed quantity for the packing list
     */
    public function getTotalPackedQuantityAttribute()
    {
        return $this->packingListLines->sum('packed_quantity');
    }

    /**
     * Check if packing list is fully packed
     */
    public function getIsFullyPackedAttribute()
    {
        $deliveryLines = $this->delivery->deliveryLines;
        $totalDeliveryQuantity = $deliveryLines->sum('delivered_quantity');
        $totalPackedQuantity = $this->packingListLines->sum('packed_quantity');
        
        return $totalPackedQuantity >= $totalDeliveryQuantity;
    }
}
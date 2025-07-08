<?php

namespace App\Models\Manufacturing;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Routing extends Model
{
    use HasFactory;

    protected $table = 'routings';
    protected $primaryKey = 'routing_id';
    public $timestamps = false;

    protected $fillable = [
        'item_id',
        'routing_code',
        'revision',
        'effective_date',
        'status',
        'cavity',
        'process',
        'set_jump',
        'yield',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    /**
     * Get the product that owns the routing.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'item_id');
    }

    /**
     * Get the routing operations for the routing.
     */
    public function routingOperations(): HasMany
    {
        return $this->hasMany(RoutingOperation::class, 'routing_id', 'routing_id');
    }

    /**
     * Get the work orders for the routing.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'routing_id', 'routing_id');
    }
}

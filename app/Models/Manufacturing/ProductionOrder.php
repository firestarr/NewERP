<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    use HasFactory;

    protected $table = 'production_orders';
    protected $primaryKey = 'production_id';
    public $timestamps = false;

    protected $fillable = [
        'wo_id',
        'production_number',
        'production_date',
        'planned_quantity',
        'actual_quantity',
        'status',
    ];

    protected $casts = [
        'production_date' => 'date',
    ];

    /**
     * Get the work order that owns the production order.
     */
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id', 'wo_id');
    }

    /**
     * Get the production consumptions for the production order.
     */
    public function productionConsumptions(): HasMany
    {
        return $this->hasMany(ProductionConsumption::class, 'production_id', 'production_id');
    }

    /**
     * Get the job tickets for the production order.
     */
    public function jobTickets(): HasMany
    {
        return $this->hasMany(JobTicket::class, 'production_id', 'production_id');
    }
}

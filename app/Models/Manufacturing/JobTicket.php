<?php

namespace App\Models\Manufacturing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobTicket extends Model
{
    use HasFactory;

    protected $table = 'job_tickets';
    protected $primaryKey = 'ticket_id';

    protected $fillable = [
        'item',
        'uom',
        'qty_completed',
        'ref_jo_no',
        'issue_date_jo',
        'qty_jo',
        'customer',
        'production_id',
        'fgrn_no',
        'date',
    ];

    protected $casts = [
        'issue_date_jo' => 'date',
        'qty_completed' => 'float',
        'qty_jo' => 'float',
    ];

    /**
     * Get the production order that owns the job ticket.
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_id', 'production_id');
    }

    /**
     * Get the customer related to the job ticket by matching customer name.
     */
    public function customerRelation(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sales\Customer::class, 'customer', 'name');
    }
}

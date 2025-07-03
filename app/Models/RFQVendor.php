<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RFQVendor extends Model
{
    use HasFactory;

    protected $table = 'rfq_vendors';
    
    protected $fillable = [
        'rfq_id',
        'vendor_id',
        'status',
        'selected_at',
        'sent_at'
    ];

    protected $casts = [
        'selected_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function requestForQuotation()
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Scope untuk vendor yang sudah dipilih
     */
    public function scopeSelected($query)
    {
        return $query->where('status', 'selected');
    }

    /**
     * Scope untuk vendor yang sudah dikirim
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Mark vendor as sent
     */
    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);
    }
}
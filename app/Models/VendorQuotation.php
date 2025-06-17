<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorQuotation extends Model
{
    use HasFactory;

    protected $table = 'vendor_quotations';
    protected $primaryKey = 'quotation_id';
    protected $fillable = [
        'rfq_id',
        'vendor_id',
        'quotation_date',
        'validity_date',
        'status',
        'currency_code',
        'exchange_rate',
        'total_amount',
        'base_currency_total',
        'notes',
        'payment_terms',
        'delivery_terms',
        'tax_amount',
        'base_currency_tax',
        'rate_date',
        'rate_source'
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'validity_date' => 'date',
    ];

    public function requestForQuotation()
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function lines()
    {
        return $this->hasMany(VendorQuotationLine::class, 'quotation_id');
    }
}
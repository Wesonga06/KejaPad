<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MpesaPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'rent_record_id',
        'phone_number',
        'amount',
        'checkout_request_id',
        'merchant_request_id',
        'mpesa_receipt_number',
        'status',
        'result_code',
        'result_desc',
        'landlord_confirmed',
        'confirmed_at',
    ];

    protected $casts = [
        'landlord_confirmed' => 'boolean',
        'confirmed_at'       => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rentRecord()
    {
        return $this->belongsTo(RentRecord::class);
    }

    public function statusColor()
    {
        return match($this->status) {
            'confirmed' => 'success',
            'pending'   => 'warning',
            'failed'    => 'danger',
            'cancelled' => 'secondary',
            default     => 'secondary',
        };
    }
}
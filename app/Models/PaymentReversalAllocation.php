<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReversalAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_reversal_id',
        'payment_allocation_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function reversal()
    {
        return $this->belongsTo(PaymentReversal::class, 'payment_reversal_id');
    }

    public function paymentAllocation()
    {
        return $this->belongsTo(PaymentAllocation::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentReversal extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'payment_id',
        'amount',
        'reversed_credit',
        'reason',
        'status',
        'initiated_by',
        'initiator_name',
        'initiated_at',
        'approved_by',
        'approver_name',
        'approved_at',
        'rejected_by',
        'rejector_name',
        'rejection_reason',
        'rejected_at',
        'provider_reference',
        'external_refund_status',
        'external_refund_reference',
        'external_refund_confirmed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reversed_credit' => 'decimal:2',
        'initiated_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'external_refund_confirmed_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentReversalAllocation::class);
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}

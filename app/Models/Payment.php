<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'amount',
        'unallocated_amount',
        'payment_date',
        'reference',
        'method',
        'source',
        'mpesa_payment_id',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function rentRecords()
    {
        return $this->belongsToMany(RentRecord::class, 'payment_allocations')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function mpesaPayment()
    {
        return $this->belongsTo(MpesaPayment::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // Amount of this payment that has been applied to rent periods.
    public function allocatedAmount(): float
    {
        return (float) $this->allocations()->sum('amount');
    }

    public function isFullyAllocated(): bool
    {
        return (float) $this->unallocated_amount <= 0.0;
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'mpesa' => 'M-Pesa',
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            default => ucfirst(str_replace('_', ' ', $this->method)),
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'unit_id',
        'amount_due',
        'amount_paid',
        'due_date',
        'payment_date',
        'status',
        'month',
        'year',
        'transaction_code',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
    ];

    // Relationships
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'payment_allocations')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function rentNotices()
    {
        return $this->hasMany(RentNotice::class);
    }

    // Helper: outstanding balance
    public function balance()
    {
        return (float) $this->amount_due - (float) $this->amount_paid;
    }

    public function outstanding(): float
    {
        return max(0.0, $this->balance());
    }

    public function isSettled(): bool
    {
        return $this->balance() <= 0.0;
    }

    public function isOverdue(): bool
    {
        return ! $this->isSettled()
            && $this->due_date !== null
            && $this->due_date->lt(now()->startOfDay());
    }

    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) abs((int) $this->due_date->startOfDay()->diffInDays(now()->startOfDay()));
    }

    // Derived status (never rely on the stored column for display logic).
    public function effectiveStatus(): string
    {
        if ($this->isSettled()) {
            return 'paid';
        }

        if ((float) $this->amount_paid > 0) {
            return $this->isOverdue() ? 'overdue' : 'partial';
        }

        return $this->isOverdue() ? 'overdue' : 'pending';
    }

    // Helper: month name
    public function monthName()
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    // Helper: period label e.g. "March 2026"
    public function periodLabel(): string
    {
        return $this->monthName().' '.$this->year;
    }
}

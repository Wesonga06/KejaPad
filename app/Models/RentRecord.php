<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'due_date'     => 'date',
        'payment_date' => 'date',
        'amount_due'   => 'decimal:2',
        'amount_paid'  => 'decimal:2',
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

    // Helper: outstanding balance
    public function balance()
    {
        return $this->amount_due - $this->amount_paid;
    }

    // Helper: month name
    public function monthName()
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }
}
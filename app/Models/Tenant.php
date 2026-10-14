<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'property_id',
        'unit_id',
        'full_name',
        'phone',
        'email',
        'national_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'move_in_date',
        'move_out_date',
        'is_active',
    ];

    protected $casts = [
        'move_in_date' => 'date',
        'move_out_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Tenant belongs to a user account
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Tenant belongs to a property
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Tenant belongs to a unit
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // All rent charges for this tenant (one per period)
    public function rentRecords()
    {
        return $this->hasMany(RentRecord::class);
    }

    // Individual payment transactions recorded against this tenant
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Reminders and formal notices issued to this tenant
    public function rentNotices()
    {
        return $this->hasMany(RentNotice::class);
    }

    // Security deposit held for this tenancy
    public function securityDeposit()
    {
        return $this->hasOne(SecurityDeposit::class);
    }

    // Notice-to-vacate requests submitted by this tenant
    public function moveOutRequests()
    {
        return $this->hasMany(MoveOutRequest::class);
    }

    // The tenant's current open notice-to-vacate request, if any.
    public function activeMoveOutRequest()
    {
        return $this->hasOne(MoveOutRequest::class)
            ->whereNotIn('status', [
                MoveOutRequest::STATUS_COMPLETED,
                MoveOutRequest::STATUS_REJECTED,
                MoveOutRequest::STATUS_CANCELLED,
            ])
            ->latestOfMany();
    }

    // Landlord / property owner (via property)
    public function landlord()
    {
        return $this->property?->landlord;
    }

    // Unallocated payment credit held on the tenant's account.
    public function creditBalance(): float
    {
        return (float) $this->payments()->sum('unallocated_amount');
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->full_name))[0] ?: $this->full_name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'move_in_date'  => 'date',
        'move_out_date' => 'date',
        'is_active'     => 'boolean',
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
}
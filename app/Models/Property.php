<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'location',
        'description',
        'total_units',
        'photo',
        'status',
        'alert_settings',
    ];

    protected $casts = [
        'alert_settings' => 'array',
    ];

    // Property belongs to a landlord
    public function landlord()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Property has many units
    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    // Caretakers responsible for this property
    public function caretakers()
    {
        return $this->belongsToMany(User::class, 'caretaker_property');
    }

    // Early-warning alerts raised across this property.
    public function alerts()
    {
        return $this->hasMany(PropertyAlert::class);
    }

    // Invitations issued for caretakers of this property.
    public function caretakerInvitations()
    {
        return $this->hasMany(CaretakerInvitation::class);
    }

    // Count vacant units
    public function vacantUnits()
    {
        return $this->units()->where('status', 'vacant')->count();
    }

    // Count occupied units
    public function occupiedUnits()
    {
        return $this->units()->where('status', 'occupied')->count();
    }
}

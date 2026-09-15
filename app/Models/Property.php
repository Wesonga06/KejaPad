<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
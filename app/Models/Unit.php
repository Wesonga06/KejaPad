<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'unit_number',
        'rent_amount',
        'status',
        'description',
    ];

    // Unit belongs to a property
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Unit has one active tenant
    public function tenant()
    {
        return $this->hasOne(Tenant::class)->where('is_active', true);
    }

    // Unit has many tenants (history)
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isLandlord(): bool
    {
        return $this->role === 'landlord';
    }

    public function isCaretaker(): bool
    {
        return $this->role === 'caretaker';
    }

    public function isTenant(): bool
    {
        return $this->role === 'tenant';
    }

    // User has one tenant profile
    public function tenantProfile()
    {
        return $this->hasOne(Tenant::class);
    }

    // User (landlord) has many properties
    public function properties()
    {
        return $this->hasMany(Property::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PENDING = 'pending_approval';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'status',
        'approval_reason',
        'approved_at',
        'rejected_at',
        'approved_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    // Whether the account may use landlord functionality. Non-landlord roles are
    // governed by the invitation / tenancy flows instead of this gate.
    public function canAccessLandlordFeatures(): bool
    {
        return $this->isLandlord() && $this->isApproved() && (bool) $this->is_active;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Approval',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_SUSPENDED => 'Suspended',
            default => 'Approved',
        };
    }

    public function landlordApprovalActions()
    {
        return $this->hasMany(LandlordApprovalAction::class, 'user_id')->latest();
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    // User (caretaker) is responsible for many properties
    public function caretakerProperties()
    {
        return $this->belongsToMany(Property::class, 'caretaker_property');
    }

    // Invitations this landlord has issued to prospective caretakers.
    public function caretakerInvitations()
    {
        return $this->hasMany(CaretakerInvitation::class, 'landlord_id');
    }
}

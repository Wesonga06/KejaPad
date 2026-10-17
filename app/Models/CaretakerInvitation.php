<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CaretakerInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REDEEMED = 'redeemed';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'landlord_id',
        'property_id',
        'email',
        'name',
        'code',
        'code_hash',
        'status',
        'expires_at',
        'redeemed_at',
        'redeemed_by',
        'revoked_at',
        'created_by',
    ];

    protected $casts = [
        'code' => 'encrypted',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // Landlord who issued the invitation.
    public function landlord()
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    // Property the invited caretaker will manage.
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function redeemedBy()
    {
        return $this->belongsTo(User::class, 'redeemed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function isRedeemed(): bool
    {
        return $this->status === self::STATUS_REDEEMED;
    }

    public function isExpired(): bool
    {
        if ($this->isRedeemed() || $this->isRevoked()) {
            return false;
        }

        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRedeemable(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->isExpired();
    }

    // Derived status used in listings (pending / redeemed / revoked / expired).
    public function displayStatus(): string
    {
        if ($this->isRedeemed()) {
            return self::STATUS_REDEEMED;
        }

        if ($this->isRevoked()) {
            return self::STATUS_REVOKED;
        }

        return $this->isExpired() ? 'expired' : self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return ucfirst($this->displayStatus());
    }

    // True when this invitation is restricted to a specific caretaker email.
    public function isEmailRestricted(): bool
    {
        return filled($this->email);
    }

    public function matchesEmail(string $email): bool
    {
        return ! $this->isEmailRestricted() || Str::lower($this->email) === Str::lower(trim($email));
    }

    public static function generateCode(): string
    {
        return Str::random(48);
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', trim($code));
    }
}

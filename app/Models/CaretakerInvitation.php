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

        return $this->expires_at === null || $this->expires_at->isPast();
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

    public function maskedCode(): string
    {
        return self::maskCode((string) $this->code);
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
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $secret = '';

        for ($index = 0; $index < 8; $index++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return 'KEJA-'.substr($secret, 0, 4).'-'.substr($secret, 4, 4);
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', self::normalizeCode($code));
    }

    public static function normalizeCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/u', '', trim($code)));
    }

    public static function maskCode(string $code): string
    {
        if (str_starts_with(strtoupper($code), 'KEJA-') && strlen($code) >= 14) {
            return 'KEJA-••••-••••';
        }

        return '••••••••••••';
    }
}

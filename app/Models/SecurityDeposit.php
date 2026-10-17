<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityDeposit extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'unit_id',
        'required_amount',
        'received_amount',
        'policy_months',
        'status',
        'verified_at',
        'verified_by',
        'note',
        'exception_reason',
        'exception_approved_at',
        'exception_approved_by',
    ];

    protected $casts = [
        'required_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'policy_months' => 'decimal:2',
        'verified_at' => 'datetime',
        'exception_approved_at' => 'datetime',
    ];

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

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function transactions()
    {
        return $this->hasMany(DepositTransaction::class);
    }

    public function receipts()
    {
        return $this->hasMany(DepositTransaction::class)->where('type', DepositTransaction::TYPE_RECEIPT);
    }

    public function deductions()
    {
        return $this->hasMany(DepositTransaction::class)->where('type', DepositTransaction::TYPE_DEDUCTION);
    }

    public function refunds()
    {
        return $this->hasMany(DepositTransaction::class)->where('type', DepositTransaction::TYPE_REFUND);
    }

    // Outstanding amount still to be received from the tenant.
    public function outstanding(): float
    {
        return max(0.0, (float) $this->required_amount - (float) $this->received_amount);
    }

    public function isSatisfied(): bool
    {
        return (float) $this->received_amount >= (float) $this->required_amount;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    // An authorized, reasoned exception waives the outstanding deposit for move-in.
    public function hasException(): bool
    {
        return $this->exception_approved_at !== null && filled($this->exception_reason);
    }

    public function exceptionApprovedBy()
    {
        return $this->belongsTo(User::class, 'exception_approved_by');
    }

    // Total confirmed (approved or completed) deductions.
    public function approvedDeductions(): float
    {
        return (float) $this->deductions()
            ->whereIn('status', [DepositTransaction::STATUS_APPROVED, DepositTransaction::STATUS_COMPLETED])
            ->sum('amount');
    }

    // Total refunds actually completed.
    public function completedRefunds(): float
    {
        return (float) $this->refunds()
            ->where('status', DepositTransaction::STATUS_COMPLETED)
            ->sum('amount');
    }

    // Refunds that have been proposed or approved but not yet paid out.
    public function pendingRefunds(): float
    {
        return (float) $this->refunds()
            ->whereIn('status', [DepositTransaction::STATUS_PROPOSED, DepositTransaction::STATUS_APPROVED])
            ->sum('amount');
    }

    // Net refundable after approved deductions.
    public function netRefundable(): float
    {
        return max(0.0, (float) $this->received_amount - $this->approvedDeductions() - $this->completedRefunds());
    }

    // Amount that may still be refunded, taking pending (unpaid) refunds into
    // account so the same balance cannot be proposed twice.
    public function availableForRefund(): float
    {
        return max(0.0, $this->netRefundable() - $this->pendingRefunds());
    }

    public function settlement()
    {
        return $this->hasOne(DepositSettlement::class);
    }

    public function derivedStatus(): string
    {
        if ($this->completedRefunds() > 0) {
            return $this->netRefundable() > 0
                ? self::STATUS_PARTIALLY_REFUNDED
                : self::STATUS_REFUNDED;
        }

        if ($this->isSatisfied()) {
            return $this->isVerified() ? self::STATUS_VERIFIED : self::STATUS_RECEIVED;
        }

        return (float) $this->received_amount > 0
            ? self::STATUS_PARTIAL
            : self::STATUS_PENDING;
    }

    public function statusLabel(): string
    {
        return match ($this->derivedStatus()) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PARTIAL => 'Partially paid',
            self::STATUS_RECEIVED => 'Received',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_REFUNDED => 'Refunded',
            self::STATUS_PARTIALLY_REFUNDED => 'Partially refunded',
            default => ucfirst(str_replace('_', ' ', $this->derivedStatus())),
        };
    }

    public function statusColor(): string
    {
        return match ($this->derivedStatus()) {
            self::STATUS_VERIFIED, self::STATUS_REFUNDED => 'success',
            self::STATUS_RECEIVED => 'primary',
            self::STATUS_PARTIAL, self::STATUS_PARTIALLY_REFUNDED => 'warning',
            self::STATUS_PENDING => 'secondary',
            default => 'secondary',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->derivedStatus()) {
            self::STATUS_VERIFIED, self::STATUS_REFUNDED => 'kp-badge-success',
            self::STATUS_RECEIVED => 'kp-badge-purple',
            self::STATUS_PARTIAL, self::STATUS_PARTIALLY_REFUNDED => 'kp-badge-warning',
            default => 'kp-badge-muted',
        };
    }
}

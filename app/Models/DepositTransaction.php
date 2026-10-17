<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositTransaction extends Model
{
    use HasFactory;

    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_DEDUCTION = 'deduction';

    public const TYPE_REFUND = 'refund';

    public const STATUS_RECORDED = 'recorded';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_PROPOSED = 'proposed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'security_deposit_id',
        'type',
        'amount',
        'method',
        'reference',
        'status',
        'reason',
        'evidence',
        'tenant_comment',
        'disputed',
        'responded_at',
        'requires_authorisation',
        'move_out_request_id',
        'recorded_by',
        'verified_at',
        'verified_by',
        'approved_at',
        'approved_by',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'disputed' => 'boolean',
        'requires_authorisation' => 'boolean',
        'responded_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function securityDeposit()
    {
        return $this->belongsTo(SecurityDeposit::class);
    }

    public function moveOutRequest()
    {
        return $this->belongsTo(MoveOutRequest::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_RECEIPT => 'Deposit received',
            self::TYPE_DEDUCTION => 'Deduction',
            self::TYPE_REFUND => 'Refund',
            default => ucfirst($this->type),
        };
    }

    public function isDisputed(): bool
    {
        return (bool) $this->disputed;
    }

    // A refund that has been proposed or approved but not yet paid.
    public function isPendingRefund(): bool
    {
        return $this->type === self::TYPE_REFUND
            && in_array($this->status, [self::STATUS_PROPOSED, self::STATUS_APPROVED], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_RECORDED => 'Recorded',
            self::STATUS_VERIFIED => 'Verified',
            self::STATUS_PROPOSED => 'Proposed',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REVERSED => 'Reversed',
            default => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED, self::STATUS_COMPLETED => 'success',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_RECORDED, self::STATUS_PROPOSED => 'warning',
            self::STATUS_REVERSED => 'secondary',
            default => 'secondary',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_VERIFIED, self::STATUS_COMPLETED => 'kp-badge-success',
            self::STATUS_APPROVED => 'kp-badge-purple',
            self::STATUS_RECORDED, self::STATUS_PROPOSED => 'kp-badge-warning',
            default => 'kp-badge-muted',
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            self::TYPE_RECEIPT => 'kp-badge-success',
            self::TYPE_DEDUCTION => 'kp-badge-warning',
            self::TYPE_REFUND => 'kp-badge-purple',
            default => 'kp-badge-muted',
        };
    }
}

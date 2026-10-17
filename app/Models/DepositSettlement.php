<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A transparent settlement statement for a single move-out. It binds the
 * existing security deposit ledger and move-out inspections together, records
 * what was proposed, the tenant's response, the landlord's final decision and
 * the resulting refund — without duplicating deposit, inspection or tenancy
 * records.
 */
class DepositSettlement extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_AWAITING_TENANT = 'awaiting_tenant';

    public const STATUS_TENANT_RESPONDED = 'tenant_responded';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SETTLED = 'settled';

    protected $fillable = [
        'move_out_request_id',
        'security_deposit_id',
        'status',
        'held_amount',
        'proposed_deductions',
        'proposed_refund',
        'approved_refund',
        'refunded_amount',
        'retained_amount',
        'landlord_findings',
        'decision_reason',
        'tenant_comment',
        'sent_at',
        'acknowledged_at',
        'responded_at',
        'decided_at',
        'settled_at',
        'decided_by',
        'settled_by',
    ];

    protected $casts = [
        'held_amount' => 'decimal:2',
        'proposed_deductions' => 'decimal:2',
        'proposed_refund' => 'decimal:2',
        'approved_refund' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'retained_amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'responded_at' => 'datetime',
        'decided_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Preparing',
            self::STATUS_AWAITING_TENANT => 'Awaiting Tenant Review',
            self::STATUS_TENANT_RESPONDED => 'Tenant Responded',
            self::STATUS_DISPUTED => 'Disputed',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_SETTLED => 'Settled',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_SETTLED => 'success',
            self::STATUS_APPROVED => 'primary',
            self::STATUS_DISPUTED => 'danger',
            self::STATUS_TENANT_RESPONDED => 'info',
            self::STATUS_AWAITING_TENANT => 'warning',
            default => 'secondary',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SETTLED => 'kp-badge-success',
            self::STATUS_APPROVED => 'kp-badge-purple',
            self::STATUS_DISPUTED => 'kp-badge-danger',
            self::STATUS_TENANT_RESPONDED => 'kp-badge-gold',
            self::STATUS_AWAITING_TENANT => 'kp-badge-warning',
            default => 'kp-badge-muted',
        };
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isAwaitingTenant(): bool
    {
        return $this->status === self::STATUS_AWAITING_TENANT;
    }

    public function isDisputed(): bool
    {
        return $this->status === self::STATUS_DISPUTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_SETTLED;
    }

    // A statement only becomes visible to the tenant once it has been sent.
    public function isVisibleToTenant(): bool
    {
        return $this->sent_at !== null;
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function moveOutRequest()
    {
        return $this->belongsTo(MoveOutRequest::class);
    }

    public function securityDeposit()
    {
        return $this->belongsTo(SecurityDeposit::class);
    }

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function settledBy()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function events()
    {
        return $this->hasMany(DepositSettlementEvent::class)->orderBy('id');
    }

    // Current deduction proposals and approvals live on the deposit ledger.
    public function deductions()
    {
        return $this->securityDeposit
            ? $this->securityDeposit->deductions()
            : DepositTransaction::query()->whereRaw('1 = 0');
    }

    public function proposedDeductions()
    {
        return $this->deductions()->where('status', DepositTransaction::STATUS_PROPOSED);
    }

    public function approvedDeductions()
    {
        return $this->securityDeposit
            ? $this->securityDeposit->approvedDeductions()
            : 0.0;
    }

    // The refund the tenant would receive if every current proposal is approved.
    public function projectedRefund(): float
    {
        if (! $this->securityDeposit) {
            return 0.0;
        }

        $pending = (float) $this->securityDeposit->deductions()
            ->whereIn('status', [DepositTransaction::STATUS_PROPOSED, DepositTransaction::STATUS_APPROVED])
            ->sum('amount');

        return max(0.0, (float) $this->held_amount - round($pending, 2) - (float) $this->refunded_amount);
    }

    public function hasDisputedDeductions(): bool
    {
        return $this->securityDeposit
            ? $this->securityDeposit->deductions()->where('disputed', true)->exists()
            : false;
    }
}

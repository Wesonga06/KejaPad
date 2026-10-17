<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveOutRequest extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_AWAITING_TENANT_INFO = 'awaiting_tenant_info';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_INSPECTION_SCHEDULED = 'inspection_scheduled';

    public const STATUS_INSPECTION_COMPLETED = 'inspection_completed';

    public const STATUS_AWAITING_CLEARANCE = 'awaiting_clearance';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'tenant_id',
        'property_id',
        'unit_id',
        'status',
        'submitted_at',
        'acknowledged_at',
        'proposed_move_out_date',
        'notice_period_months',
        'earliest_permitted_date',
        'reason',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
        'clarification_message',
        'actual_move_out_date',
        'clearance_decision',
        'cleared_by',
        'cleared_at',
        'notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'proposed_move_out_date' => 'date',
        'earliest_permitted_date' => 'date',
        'notice_period_months' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'actual_move_out_date' => 'date',
        'cleared_at' => 'datetime',
    ];

    /**
     * The full status workflow. Each status maps to the statuses it may move to.
     *
     * @return array<string, array<int, string>>
     */
    public static function transitions(): array
    {
        return [
            self::STATUS_SUBMITTED => [
                self::STATUS_UNDER_REVIEW,
                self::STATUS_AWAITING_TENANT_INFO,
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_UNDER_REVIEW => [
                self::STATUS_AWAITING_TENANT_INFO,
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_AWAITING_TENANT_INFO => [
                self::STATUS_UNDER_REVIEW,
                self::STATUS_APPROVED,
                self::STATUS_REJECTED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_APPROVED => [
                self::STATUS_INSPECTION_SCHEDULED,
                self::STATUS_AWAITING_CLEARANCE,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_INSPECTION_SCHEDULED => [
                self::STATUS_INSPECTION_COMPLETED,
                self::STATUS_AWAITING_CLEARANCE,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_INSPECTION_COMPLETED => [
                self::STATUS_AWAITING_CLEARANCE,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_AWAITING_CLEARANCE => [
                self::STATUS_COMPLETED,
                self::STATUS_CANCELLED,
            ],
            self::STATUS_COMPLETED => [],
            self::STATUS_REJECTED => [],
            self::STATUS_CANCELLED => [],
        ];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::transitions()[$this->status] ?? [], true);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_UNDER_REVIEW => 'Under Review',
            self::STATUS_AWAITING_TENANT_INFO => 'Awaiting Tenant Information',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_INSPECTION_SCHEDULED => 'Inspection Scheduled',
            self::STATUS_INSPECTION_COMPLETED => 'Inspection Completed',
            self::STATUS_AWAITING_CLEARANCE => 'Awaiting Clearance',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public function statusLabelText(): string
    {
        return self::statusLabel($this->status);
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_APPROVED, self::STATUS_AWAITING_CLEARANCE => 'primary',
            self::STATUS_SUBMITTED, self::STATUS_UNDER_REVIEW => 'info',
            self::STATUS_AWAITING_TENANT_INFO, self::STATUS_INSPECTION_SCHEDULED,
            self::STATUS_INSPECTION_COMPLETED => 'warning',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'kp-badge-success',
            self::STATUS_APPROVED, self::STATUS_UNDER_REVIEW => 'kp-badge-purple',
            self::STATUS_SUBMITTED => 'kp-badge-gold',
            self::STATUS_AWAITING_TENANT_INFO, self::STATUS_INSPECTION_SCHEDULED,
            self::STATUS_INSPECTION_COMPLETED, self::STATUS_AWAITING_CLEARANCE => 'kp-badge-warning',
            self::STATUS_REJECTED => 'kp-badge-danger',
            self::STATUS_CANCELLED => 'kp-badge-muted',
            default => 'kp-badge-muted',
        };
    }

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

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function clearedBy()
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function inspection()
    {
        return $this->hasOne(MoveOutInspection::class)->latestOfMany();
    }

    public function inspections()
    {
        return $this->hasMany(MoveOutInspection::class);
    }

    public function depositTransactions()
    {
        return $this->hasMany(DepositTransaction::class);
    }
}

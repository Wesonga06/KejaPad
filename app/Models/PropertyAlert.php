<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A rule-based early-warning alert about a condition on a property, unit or
 * tenancy. Alerts never duplicate the underlying records they describe: they
 * reference them through the subject morph and are refreshed from the live
 * data on every evaluation, so resolving the underlying issue clears the alert.
 */
class PropertyAlert extends Model
{
    use HasFactory;

    // Rule identifiers. Each maps to a single condition the engine can detect.
    public const TYPE_RENT_OVERDUE = 'rent_overdue';

    public const TYPE_REPEATED_ARREARS = 'repeated_arrears';

    public const TYPE_MAINTENANCE_DUE = 'maintenance_due';

    public const TYPE_MAINTENANCE_OVERDUE = 'maintenance_overdue';

    public const TYPE_RECURRING_MAINTENANCE = 'recurring_maintenance';

    public const TYPE_INSPECTION_DUE = 'inspection_due';

    public const TYPE_INSPECTION_OVERDUE = 'inspection_overdue';

    public const TYPE_TENANCY_ENDING = 'tenancy_ending';

    public const TYPE_PROLONGED_VACANCY = 'prolonged_vacancy';

    public const TYPE_TURNOVER_STALLED = 'turnover_stalled';

    public const TYPE_SETTLEMENT_AWAITING = 'settlement_awaiting';

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'property_id',
        'unit_id',
        'tenant_id',
        'type',
        'severity',
        'status',
        'condition_key',
        'title',
        'reason',
        'recommended_action',
        'subject_type',
        'subject_id',
        'metadata',
        'detected_at',
        'last_seen_at',
        'notified_at',
        'acknowledged_at',
        'acknowledged_by',
        'dismissed_at',
        'dismissed_by',
        'dismissed_reason',
        'resolved_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'detected_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'notified_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'dismissed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_RENT_OVERDUE => 'Rent overdue',
            self::TYPE_REPEATED_ARREARS => 'Repeated arrears',
            self::TYPE_MAINTENANCE_DUE => 'Maintenance due soon',
            self::TYPE_MAINTENANCE_OVERDUE => 'Maintenance overdue',
            self::TYPE_RECURRING_MAINTENANCE => 'Recurring maintenance',
            self::TYPE_INSPECTION_DUE => 'Inspection due soon',
            self::TYPE_INSPECTION_OVERDUE => 'Inspection overdue',
            self::TYPE_TENANCY_ENDING => 'Tenancy ending',
            self::TYPE_PROLONGED_VACANCY => 'Prolonged vacancy',
            self::TYPE_TURNOVER_STALLED => 'Turnover stalled',
            self::TYPE_SETTLEMENT_AWAITING => 'Settlement awaiting decision',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function severities(): array
    {
        return [
            self::SEVERITY_INFO => 'Information',
            self::SEVERITY_WARNING => 'Needs attention',
            self::SEVERITY_CRITICAL => 'Critical',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            self::STATUS_DISMISSED => 'Dismissed',
            self::STATUS_RESOLVED => 'Resolved',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }

    public function severityLabel(): string
    {
        return self::severities()[$this->severity] ?? ucfirst((string) $this->severity);
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function severityBadgeClass(): string
    {
        return match ($this->severity) {
            self::SEVERITY_CRITICAL => 'kp-badge-danger',
            self::SEVERITY_WARNING => 'kp-badge-warning',
            default => 'kp-badge-purple',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'kp-badge-danger',
            self::STATUS_ACKNOWLEDGED => 'kp-badge-warning',
            self::STATUS_RESOLVED => 'kp-badge-success',
            self::STATUS_DISMISSED => 'kp-badge-muted',
            default => 'kp-badge-muted',
        };
    }

    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    public function isDismissed(): bool
    {
        return $this->status === self::STATUS_DISMISSED;
    }

    public function isAcknowledged(): bool
    {
        return $this->status === self::STATUS_ACKNOWLEDGED;
    }

    // An alert is still "unresolved" while active or acknowledged. Dismissed
    // alerts are treated as suppressed but not resolved.
    public function isUnresolved(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_ACKNOWLEDGED], true);
    }

    public function canBeDismissed(): bool
    {
        return ! $this->isCritical();
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function dismissedBy()
    {
        return $this->belongsTo(User::class, 'dismissed_by');
    }

    public function events()
    {
        return $this->hasMany(PropertyAlertEvent::class)->orderBy('id');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_ACKNOWLEDGED]);
    }
}

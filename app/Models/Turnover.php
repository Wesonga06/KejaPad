<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The coordinated turnover workspace for a single move-out. It binds the
 * existing move-out request, inspections, maintenance tasks, deposit settlement
 * and unit occupancy together, and records the turnover-specific decisions
 * (handover, readiness confirmation and the incoming tenancy link) without
 * duplicating any of the underlying records.
 */
class Turnover extends Model
{
    use HasFactory;

    public const STATUS_MOVE_OUT_PLANNED = 'move_out_planned';

    public const STATUS_INSPECTION_PENDING = 'inspection_pending';

    public const STATUS_REPAIRS_REQUIRED = 'repairs_required';

    public const STATUS_AWAITING_VERIFICATION = 'awaiting_verification';

    public const STATUS_READY_FOR_LETTING = 'ready_for_letting';

    public const STATUS_NEW_TENANCY_PENDING = 'new_tenancy_pending';

    public const STATUS_OCCUPIED = 'occupied';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'move_out_request_id',
        'property_id',
        'unit_id',
        'outgoing_tenant_id',
        'next_tenant_id',
        'status',
        'handover_notes',
        'handover_recorded_at',
        'handover_recorded_by',
        'readiness_notes',
        'readiness_confirmed_at',
        'readiness_confirmed_by',
        'new_tenancy_started_at',
        'completed_at',
        'cancelled_at',
        'last_activity_at',
    ];

    protected $casts = [
        'handover_recorded_at' => 'datetime',
        'readiness_confirmed_at' => 'datetime',
        'new_tenancy_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_MOVE_OUT_PLANNED => 'Move-Out Planned',
            self::STATUS_INSPECTION_PENDING => 'Inspection Pending',
            self::STATUS_REPAIRS_REQUIRED => 'Repairs Required',
            self::STATUS_AWAITING_VERIFICATION => 'Awaiting Verification',
            self::STATUS_READY_FOR_LETTING => 'Ready for Letting',
            self::STATUS_NEW_TENANCY_PENDING => 'New Tenancy Pending',
            self::STATUS_OCCUPIED => 'Occupied Under New Tenancy',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_READY_FOR_LETTING => 'kp-badge-success',
            self::STATUS_OCCUPIED => 'kp-badge-purple',
            self::STATUS_NEW_TENANCY_PENDING => 'kp-badge-gold',
            self::STATUS_REPAIRS_REQUIRED => 'kp-badge-danger',
            self::STATUS_AWAITING_VERIFICATION => 'kp-badge-warning',
            self::STATUS_INSPECTION_PENDING => 'kp-badge-warning',
            self::STATUS_CANCELLED => 'kp-badge-muted',
            default => 'kp-badge-muted',
        };
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isReadyForLetting(): bool
    {
        return $this->readiness_confirmed_at !== null && ! $this->isCancelled();
    }

    public function isOccupied(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function isClosed(): bool
    {
        return $this->isCancelled() || $this->isOccupied();
    }

    public function moveOutRequest()
    {
        return $this->belongsTo(MoveOutRequest::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function outgoingTenant()
    {
        return $this->belongsTo(Tenant::class, 'outgoing_tenant_id');
    }

    public function nextTenant()
    {
        return $this->belongsTo(Tenant::class, 'next_tenant_id');
    }

    public function readinessConfirmedBy()
    {
        return $this->belongsTo(User::class, 'readiness_confirmed_by');
    }

    public function handoverRecordedBy()
    {
        return $this->belongsTo(User::class, 'handover_recorded_by');
    }

    // Repair tasks are real maintenance requests linked to this turnover.
    public function tasks()
    {
        return $this->hasMany(MaintenanceRequest::class)->orderBy('created_at');
    }

    public function openTasks()
    {
        return $this->tasks()->whereIn('status', ['pending', 'assigned', 'in_progress']);
    }

    public function events()
    {
        return $this->hasMany(TurnoverEvent::class)->orderBy('id');
    }

    // The recorded move-out inspection for the outgoing tenancy, if any.
    public function inspection()
    {
        return $this->moveOutRequest?->inspection();
    }

    public function settlement()
    {
        return $this->moveOutRequest?->settlement();
    }

    // The move-in inspection linked to the incoming tenancy, if one exists.
    public function incomingMoveInInspection()
    {
        if (! $this->next_tenant_id) {
            return null;
        }

        return UnitInspection::where('unit_id', $this->unit_id)
            ->where('tenant_id', $this->next_tenant_id)
            ->where('type', UnitInspection::TYPE_MOVE_IN)
            ->latest('id')
            ->first();
    }
}

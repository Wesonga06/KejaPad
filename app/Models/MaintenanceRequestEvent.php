<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single immutable entry in a maintenance task's accountability ledger.
 * Entries record who did what and when — assignment, reassignment, status
 * changes, deadline changes, evidence additions, completion submissions,
 * verification decisions and caretaker correction requests.
 */
class MaintenanceRequestEvent extends Model
{
    use HasFactory;

    public const EVENT_ASSIGNED = 'assigned';

    public const EVENT_REASSIGNED = 'reassigned';

    public const EVENT_UNASSIGNED = 'unassigned';

    public const EVENT_DEADLINE_CHANGED = 'deadline_changed';

    public const EVENT_STARTED = 'started';

    public const EVENT_STATUS_CHANGED = 'status_changed';

    public const EVENT_EVIDENCE_ADDED = 'evidence_added';

    public const EVENT_COMPLETION_SUBMITTED = 'completion_submitted';

    public const EVENT_VERIFIED = 'verified';

    public const EVENT_RETURNED = 'returned';

    public const EVENT_ESCALATED = 'escalated';

    public const EVENT_CORRECTION_REQUESTED = 'correction_requested';

    protected $fillable = [
        'maintenance_request_id',
        'user_id',
        'role',
        'event',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->event) {
            self::EVENT_ASSIGNED => 'Task assigned',
            self::EVENT_REASSIGNED => 'Task reassigned',
            self::EVENT_UNASSIGNED => 'Returned to the unassigned pool',
            self::EVENT_DEADLINE_CHANGED => 'Deadline changed',
            self::EVENT_STARTED => 'Work started',
            self::EVENT_STATUS_CHANGED => 'Status changed',
            self::EVENT_EVIDENCE_ADDED => 'Evidence added',
            self::EVENT_COMPLETION_SUBMITTED => 'Completion submitted',
            self::EVENT_VERIFIED => 'Completion verified',
            self::EVENT_RETURNED => 'Returned for further work',
            self::EVENT_ESCALATED => 'Overdue escalated',
            self::EVENT_CORRECTION_REQUESTED => 'Correction requested',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }

    public function badgeClass(): string
    {
        return match ($this->event) {
            self::EVENT_ASSIGNED, self::EVENT_REASSIGNED => 'kp-badge-purple',
            self::EVENT_UNASSIGNED, self::EVENT_RETURNED => 'kp-badge-danger',
            self::EVENT_STARTED, self::EVENT_STATUS_CHANGED,
            self::EVENT_CORRECTION_REQUESTED => 'kp-badge-warning',
            self::EVENT_VERIFIED => 'kp-badge-success',
            self::EVENT_DEADLINE_CHANGED, self::EVENT_ESCALATED, self::EVENT_COMPLETION_SUBMITTED => 'kp-badge-gold',
            default => 'kp-badge-muted',
        };
    }
}

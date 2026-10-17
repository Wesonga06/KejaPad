<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An append-only entry in a turnover's history. Entries are never edited or
 * deleted, so the full sequence of stage changes, assignments, cancellations
 * and readiness approvals is preserved.
 */
class TurnoverEvent extends Model
{
    use HasFactory;

    public const EVENT_OPENED = 'opened';

    public const EVENT_STATUS_CHANGED = 'status_changed';

    public const EVENT_TASK_ADDED = 'task_added';

    public const EVENT_TASK_ASSIGNED = 'task_assigned';

    public const EVENT_TASK_RESOLVED = 'task_resolved';

    public const EVENT_TASK_VERIFIED = 'task_verified';

    public const EVENT_HANDOVER_RECORDED = 'handover_recorded';

    public const EVENT_READY_CONFIRMED = 'ready_confirmed';

    public const EVENT_ONBOARDING_STARTED = 'onboarding_started';

    public const EVENT_INCOMING_TENANT_LINKED = 'incoming_tenant_linked';

    public const EVENT_INCOMING_MOVE_IN = 'incoming_move_in';

    public const EVENT_CANCELLED = 'cancelled';

    public const EVENT_COMPLETED = 'completed';

    public const EVENT_NOTE = 'note';

    protected $fillable = [
        'turnover_id',
        'user_id',
        'role',
        'event',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function turnover()
    {
        return $this->belongsTo(Turnover::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->event) {
            self::EVENT_OPENED => 'Turnover opened',
            self::EVENT_STATUS_CHANGED => 'Status changed',
            self::EVENT_TASK_ADDED => 'Repair task added',
            self::EVENT_TASK_ASSIGNED => 'Repair task assigned',
            self::EVENT_TASK_RESOLVED => 'Repair task resolved',
            self::EVENT_TASK_VERIFIED => 'Repair completion verified',
            self::EVENT_HANDOVER_RECORDED => 'Handover recorded',
            self::EVENT_READY_CONFIRMED => 'Unit confirmed ready',
            self::EVENT_ONBOARDING_STARTED => 'Onboarding started',
            self::EVENT_INCOMING_TENANT_LINKED => 'Incoming tenancy linked',
            self::EVENT_INCOMING_MOVE_IN => 'New move-in recorded',
            self::EVENT_CANCELLED => 'Turnover cancelled',
            self::EVENT_COMPLETED => 'Turnover completed',
            default => ucfirst(str_replace('_', ' ', $this->event)),
        };
    }
}

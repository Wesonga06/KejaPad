<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An append-only entry in an alert's history. Acknowledging, dismissing,
 * reopening and resolving an alert all leave a permanent trace, so the full
 * lifecycle of a warning is preserved even after it is resolved.
 */
class PropertyAlertEvent extends Model
{
    use HasFactory;

    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_ACKNOWLEDGED = 'acknowledged';

    public const EVENT_DISMISSED = 'dismissed';

    public const EVENT_REOPENED = 'reopened';

    public const EVENT_RESOLVED = 'resolved';

    public const EVENT_NOTIFIED = 'notified';

    protected $fillable = [
        'property_alert_id',
        'user_id',
        'event',
        'message',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function alert()
    {
        return $this->belongsTo(PropertyAlert::class, 'property_alert_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->event) {
            self::EVENT_CREATED => 'Alert raised',
            self::EVENT_UPDATED => 'Details refreshed',
            self::EVENT_ACKNOWLEDGED => 'Acknowledged',
            self::EVENT_DISMISSED => 'Dismissed',
            self::EVENT_REOPENED => 'Reopened',
            self::EVENT_RESOLVED => 'Resolved',
            self::EVENT_NOTIFIED => 'Landlord notified',
            default => ucfirst(str_replace('_', ' ', (string) $this->event)),
        };
    }
}

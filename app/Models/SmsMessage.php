<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SmsMessage extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'recipient',
        'body',
        'template',
        'provider',
        'status',
        'provider_message_id',
        'error',
        'sent_by',
        'related_type',
        'related_id',
    ];

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'kp-badge-success',
            self::STATUS_FAILED => 'kp-badge-danger',
            default => 'kp-badge-warning',
        };
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }
}

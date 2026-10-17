<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function actionLabel(): string
    {
        return ucfirst(str_replace(['.', '_'], [' - ', ' '], $this->action));
    }

    public function badgeClass(): string
    {
        return match (true) {
            str_contains($this->action, 'deleted') => 'kp-badge-danger',
            str_contains($this->action, 'created') => 'kp-badge-success',
            default => 'kp-badge-purple',
        };
    }
}

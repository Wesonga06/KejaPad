<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A file attached to a maintenance task. Files live on a private disk and are
 * streamed through an authorization check (see MaintenanceEvidenceController),
 * so they are never publicly reachable by guessing a URL.
 */
class MaintenanceRequestEvidence extends Model
{
    use HasFactory;

    public const TYPE_ISSUE = 'issue';

    public const TYPE_COMPLETION = 'completion';

    protected $fillable = [
        'maintenance_request_id',
        'user_id',
        'type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'note',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isCompletion(): bool
    {
        return $this->type === self::TYPE_COMPLETION;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_ISSUE => 'Reported issue',
            self::TYPE_COMPLETION => 'Completion evidence',
            default => ucfirst(str_replace('_', ' ', (string) $this->type)),
        };
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size;

        if ($bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) floor(log($bytes, 1024));

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}

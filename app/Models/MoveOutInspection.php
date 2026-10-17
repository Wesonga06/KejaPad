<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveOutInspection extends Model
{
    use HasFactory;

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_SUBMITTED = 'submitted';

    public const CONDITION_GOOD = 'good';

    public const CONDITION_NEEDS_ATTENTION = 'needs_attention';

    public const CONDITION_DAMAGED = 'damaged';

    public const CONDITION_NA = 'na';

    protected $fillable = [
        'move_out_request_id',
        'caretaker_id',
        'status',
        'scheduled_at',
        'completed_at',
        'keys_returned',
        'keys_notes',
        'electricity_meter',
        'water_meter',
        'cleanliness',
        'general_notes',
        'photos',
        'recommendation',
        'submitted_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'keys_returned' => 'boolean',
        'photos' => 'array',
    ];

    /**
     * The standard move-out inspection checklist.
     *
     * @return array<string, array{label: string, items: array<int, string>}>
     */
    public static function checklist(): array
    {
        return [
            'walls' => [
                'label' => 'Walls, paint and ceilings',
                'items' => ['Wall condition', 'Paint condition', 'Ceiling condition'],
            ],
            'doors' => [
                'label' => 'Doors, windows and locks',
                'items' => ['Doors', 'Windows', 'Locks and keys'],
            ],
            'plumbing' => [
                'label' => 'Plumbing and bathroom fixtures',
                'items' => ['Taps and pipes', 'Toilet', 'Shower / bath', 'Sink'],
            ],
            'electrical' => [
                'label' => 'Electrical fixtures',
                'items' => ['Sockets', 'Light fittings', 'Switches'],
            ],
            'floors' => [
                'label' => 'Floors and other fittings',
                'items' => ['Flooring', 'Built-in fittings'],
            ],
            'keys' => [
                'label' => 'Keys returned',
                'items' => ['Keys returned'],
            ],
            'utilities' => [
                'label' => 'Utility meter readings',
                'items' => ['Electricity meter', 'Water meter'],
            ],
            'cleanliness' => [
                'label' => 'General cleanliness',
                'items' => ['General cleanliness'],
            ],
            'damage' => [
                'label' => 'Damage or missing items',
                'items' => ['Damage or missing items'],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function conditions(): array
    {
        return [
            self::CONDITION_GOOD => 'Good',
            self::CONDITION_NEEDS_ATTENTION => 'Needs Attention',
            self::CONDITION_DAMAGED => 'Damaged',
            self::CONDITION_NA => 'Not Applicable',
        ];
    }

    public static function conditionLabel(?string $condition): string
    {
        return self::conditions()[$condition] ?? '—';
    }

    /**
     * @return array<string, string>
     */
    public static function natures(): array
    {
        return MoveOutInspectionItem::natures();
    }

    public function natureLabel(?string $nature): string
    {
        return self::natures()[$nature] ?? '—';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ASSIGNED => 'Assigned',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_SUBMITTED => 'Submitted',
            default => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }

    public function recommendationLabel(): string
    {
        return match ($this->recommendation) {
            'recommend_clearance' => 'Recommend clearance',
            'further_action' => 'Further action required',
            default => '—',
        };
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_SUBMITTED => 'kp-badge-success',
            default => 'kp-badge-warning',
        };
    }

    public function moveOutRequest()
    {
        return $this->belongsTo(MoveOutRequest::class);
    }

    // Browser-facing URLs for the stored inspection photographs.
    public function photoUrls(): array
    {
        return collect($this->photos ?? [])
            ->map(fn ($path) => asset('storage/'.ltrim($path, '/')))
            ->values()
            ->all();
    }

    public function caretaker()
    {
        return $this->belongsTo(User::class, 'caretaker_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function items()
    {
        return $this->hasMany(MoveOutInspectionItem::class);
    }
}

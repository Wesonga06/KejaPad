<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitInspection extends Model
{
    use HasFactory;

    public const TYPE_MOVE_IN = 'move_in';

    public const TYPE_ROUTINE = 'routine';

    public const TYPE_MOVE_OUT = 'move_out';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const RECOMMEND_CLEARANCE = 'recommend_clearance';

    public const RECOMMEND_FURTHER_ACTION = 'further_action';

    protected $fillable = [
        'unit_id',
        'property_id',
        'tenant_id',
        'move_out_inspection_id',
        'type',
        'status',
        'inspection_date',
        'inspected_by',
        'created_by',
        'keys_returned',
        'keys_notes',
        'electricity_meter',
        'water_meter',
        'cleanliness',
        'general_notes',
        'photos',
        'recommendation',
        'acknowledged_at',
        'acknowledged_by',
        'acknowledgement_name',
        'acknowledgement_note',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'keys_returned' => 'boolean',
        'photos' => 'array',
        'acknowledged_at' => 'datetime',
    ];

    /**
     * Inspections share the same factual checklist and condition vocabulary as
     * the existing move-out inspections, so the two can be compared side by side
     * without maintaining a second definition.
     *
     * @return array<string, array{label: string, items: array<int, string>}>
     */
    public static function checklist(): array
    {
        return MoveOutInspection::checklist();
    }

    /**
     * @return array<string, string>
     */
    public static function conditions(): array
    {
        return MoveOutInspection::conditions();
    }

    /**
     * @return array<string, string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_MOVE_IN => 'Move-in inspection',
            self::TYPE_ROUTINE => 'Routine property inspection',
            self::TYPE_MOVE_OUT => 'Move-out inspection',
        ];
    }

    public function typeLabel(): string
    {
        return self::types()[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function statusLabel(): string
    {
        return $this->isSubmitted() ? 'Submitted' : 'Draft';
    }

    public function statusBadgeClass(): string
    {
        return $this->isSubmitted() ? 'kp-badge-success' : 'kp-badge-warning';
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function recommendationLabel(): string
    {
        return match ($this->recommendation) {
            self::RECOMMEND_CLEARANCE => 'Recommend clearance',
            self::RECOMMEND_FURTHER_ACTION => 'Further action required',
            default => '—',
        };
    }

    // Private disk URLs are served through an authorised route, never as a
    // direct public path.
    public function photoUrls(): array
    {
        return collect($this->photos ?? [])
            ->values()
            ->map(fn ($path, $index) => route('passport.photos.show', [$this, $index]))
            ->all();
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function moveOutInspection()
    {
        return $this->belongsTo(MoveOutInspection::class);
    }

    public function inspectedBy()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function items()
    {
        return $this->hasMany(UnitInspectionItem::class);
    }

    // Group the items by their area/category for display and comparison.
    public function itemsByCategory()
    {
        return $this->items->groupBy('category');
    }
}

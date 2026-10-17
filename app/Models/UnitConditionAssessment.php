<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitConditionAssessment extends Model
{
    use HasFactory;

    public const RATING_GOOD = 'good';

    public const RATING_ATTENTION = 'attention';

    public const RATING_POOR = 'poor';

    protected $fillable = [
        'unit_id',
        'property_id',
        'assessed_by',
        'created_by',
        'assessed_at',
        'rating',
        'summary',
    ];

    protected $casts = [
        'assessed_at' => 'date',
    ];

    /**
     * @return array<string, string>
     */
    public static function ratings(): array
    {
        return [
            self::RATING_GOOD => 'Good',
            self::RATING_ATTENTION => 'Needs attention',
            self::RATING_POOR => 'Poor',
        ];
    }

    public function ratingLabel(): string
    {
        return $this->rating !== null
            ? (self::ratings()[$this->rating] ?? ucfirst($this->rating))
            : 'Not rated';
    }

    public function ratingBadgeClass(): string
    {
        return match ($this->rating) {
            self::RATING_GOOD => 'kp-badge-success',
            self::RATING_ATTENTION => 'kp-badge-warning',
            self::RATING_POOR => 'kp-badge-danger',
            default => 'kp-badge-muted',
        };
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function assessedBy()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

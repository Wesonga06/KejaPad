<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveOutInspectionItem extends Model
{
    use HasFactory;

    public const NATURE_NEW_DAMAGE = 'new_damage';

    public const NATURE_PRE_EXISTING = 'pre_existing';

    public const NATURE_WEAR_TEAR = 'wear_tear';

    protected $fillable = [
        'move_out_inspection_id',
        'category',
        'label',
        'condition',
        'nature',
        'comment',
    ];

    /**
     * Classifications the caretaker may attach to an observed condition. They
     * describe the evidence only; none of them attributes liability.
     *
     * @return array<string, string>
     */
    public static function natures(): array
    {
        return [
            self::NATURE_NEW_DAMAGE => 'Possible new damage',
            self::NATURE_PRE_EXISTING => 'Pre-existing defect',
            self::NATURE_WEAR_TEAR => 'Ordinary wear and tear',
        ];
    }

    public function inspection()
    {
        return $this->belongsTo(MoveOutInspection::class, 'move_out_inspection_id');
    }

    public function natureLabel(): string
    {
        return self::natures()[$this->nature] ?? '—';
    }

    public function natureColor(): string
    {
        return match ($this->nature) {
            self::NATURE_NEW_DAMAGE => 'danger',
            self::NATURE_PRE_EXISTING => 'secondary',
            self::NATURE_WEAR_TEAR => 'warning',
            default => 'secondary',
        };
    }

    public function isPossibleNewDamage(): bool
    {
        return $this->nature === self::NATURE_NEW_DAMAGE;
    }

    public function conditionLabel(): string
    {
        return MoveOutInspection::conditionLabel($this->condition);
    }

    public function conditionColor(): string
    {
        return match ($this->condition) {
            MoveOutInspection::CONDITION_GOOD => 'success',
            MoveOutInspection::CONDITION_NEEDS_ATTENTION => 'warning',
            MoveOutInspection::CONDITION_DAMAGED => 'danger',
            default => 'secondary',
        };
    }
}

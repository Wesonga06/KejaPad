<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoveOutInspectionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'move_out_inspection_id',
        'category',
        'label',
        'condition',
        'comment',
    ];

    public function inspection()
    {
        return $this->belongsTo(MoveOutInspection::class, 'move_out_inspection_id');
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

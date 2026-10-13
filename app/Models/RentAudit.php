<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentAudit extends Model
{
    use HasFactory;

    public const ACTION_PAYMENT_RECORDED = 'payment_recorded';

    public const ACTION_PAYMENT_REALLOCATED = 'payment_reallocated';

    public const ACTION_CREDIT_APPLIED = 'credit_applied';

    public const ACTION_NOTICE_ISSUED = 'notice_issued';

    public const ACTION_RECORD_RECALCULATED = 'record_recalculated';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    public static function record(string $action, Model $auditable, array $newValues = [], array $oldValues = [], ?int $userId = null): self
    {
        return self::create([
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
        ]);
    }
}

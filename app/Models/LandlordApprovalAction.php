<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandlordApprovalAction extends Model
{
    use HasFactory;

    public const ACTION_SUBMITTED = 'submitted';

    public const ACTION_APPROVED = 'approved';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_SUSPENDED = 'suspended';

    public const ACTION_REINSTATED = 'reinstated';

    protected $fillable = [
        'user_id',
        'action',
        'reason',
        'performed_by',
    ];

    public function landlord()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function actionLabel(): string
    {
        return ucfirst(str_replace('_', ' ', $this->action));
    }
}

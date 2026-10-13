<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentNotice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'rent_record_id',
        'type',
        'stage',
        'issued_at',
        'recipient',
        'delivery_status',
        'deadline',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'deadline' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function rentRecord()
    {
        return $this->belongsTo(RentRecord::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'payment_reminder' => 'Payment Reminder',
            'follow_up_reminder' => 'Follow-up Reminder',
            'escalation' => 'Escalation Notice',
            'formal_notice' => 'Formal Notice',
            'demand_letter' => 'Demand Letter',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    public function deliveryStatusColor(): string
    {
        return match ($this->delivery_status) {
            'delivered' => 'success',
            'acknowledged' => 'primary',
            'sent' => 'info',
            'failed' => 'danger',
            default => 'secondary',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'unit_id',
        'assigned_to',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'photo',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function categoryLabel()
    {
        return ucfirst($this->category);
    }

    public function statusColor()
    {
        return match($this->status) {
            'pending'     => 'secondary',
            'assigned'    => 'info',
            'in_progress' => 'warning',
            'resolved'    => 'success',
            'closed'      => 'dark',
            default       => 'secondary',
        };
    }

    public function priorityColor()
    {
        return match($this->priority) {
            'low'    => 'success',
            'medium' => 'warning',
            'high'   => 'danger',
            default  => 'secondary',
        };
    }
}
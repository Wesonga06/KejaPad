<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'property_id',
        'unit_id',
        'turnover_id',
        'assigned_to',
        'assigned_at',
        'assigned_by',
        'started_at',
        'escalated_at',
        'returned_at',
        'returned_by',
        'return_reason',
        'title',
        'description',
        'category',
        'priority',
        'due_date',
        'status',
        'photo',
        'resolution_notes',
        'resolved_at',
        'verified_at',
        'verified_by',
        'verification_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'verified_at' => 'datetime',
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'escalated_at' => 'datetime',
        'returned_at' => 'datetime',
        'due_date' => 'date',
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

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // The turnover workspace this repair task belongs to, if any.
    public function turnover()
    {
        return $this->belongsTo(Turnover::class);
    }

    // The append-only accountability ledger for this task.
    public function events()
    {
        return $this->hasMany(MaintenanceRequestEvent::class)->orderBy('id');
    }

    // Supporting files: the reported issue and the caretaker's completion evidence.
    public function evidence()
    {
        return $this->hasMany(MaintenanceRequestEvidence::class);
    }

    public function isTurnoverTask(): bool
    {
        return $this->turnover_id !== null;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['pending', 'assigned', 'in_progress'], true);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    // Submitted for verification but not yet confirmed by the landlord.
    public function isAwaitingVerification(): bool
    {
        return in_array($this->status, ['resolved', 'closed'], true) && $this->verified_at === null;
    }

    // Returned by the landlord for further work; the caretaker must act again.
    public function isReturnedForRework(): bool
    {
        return $this->returned_at !== null;
    }

    public function isOverdue(?Carbon $asOf = null): bool
    {
        if (! $this->isOpen() || $this->due_date === null) {
            return false;
        }

        $date = ($asOf ?? now())->copy()->startOfDay();

        return $this->due_date->lt($date);
    }

    // Requests a caretaker should see: their properties', plus anything assigned to them
    public function scopeVisibleToCaretaker($query, User $user)
    {
        $propertyIds = $user->caretakerProperties()->pluck('properties.id');

        return $query->where(function ($q) use ($propertyIds, $user) {
            $q->whereIn('property_id', $propertyIds)
                ->orWhere('assigned_to', $user->id);
        });
    }

    // Tasks owned by a landlord's portfolio.
    public function scopeVisibleToLandlord($query, User $user)
    {
        return $query->whereIn('property_id', Property::where('user_id', $user->id)->pluck('id'));
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['pending', 'assigned', 'in_progress']);
    }

    public function scopeAwaitingVerification($query)
    {
        return $query->whereIn('status', ['resolved', 'closed'])->whereNull('verified_at');
    }

    public function scopeOverdue($query, ?Carbon $asOf = null)
    {
        return $query->unresolved()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', ($asOf ?? now())->toDateString());
    }

    public function categoryLabel()
    {
        return ucfirst($this->category);
    }

    public function statusColor()
    {
        return match ($this->status) {
            'pending' => 'secondary',
            'assigned' => 'info',
            'in_progress' => 'warning',
            'resolved' => 'success',
            'closed' => 'dark',
            default => 'secondary',
        };
    }

    public function priorityColor()
    {
        return match ($this->priority) {
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            default => 'secondary',
        };
    }
}

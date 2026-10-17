<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'unit_number',
        'rent_amount',
        'status',
        'description',
    ];

    // Unit belongs to a property
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // Unit has one active tenant
    public function tenant()
    {
        return $this->hasOne(Tenant::class)->where('is_active', true);
    }

    // Unit has many tenants (history)
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    // ============ Property Passport ============

    // Move-in and routine condition inspections recorded against this unit.
    public function inspections()
    {
        return $this->hasMany(UnitInspection::class);
    }

    // Documents and evidence held in the unit's passport.
    public function documents()
    {
        return $this->hasMany(UnitDocument::class);
    }

    // Subjective condition assessments authored by authorised users.
    public function conditionAssessments()
    {
        return $this->hasMany(UnitConditionAssessment::class);
    }

    // All maintenance raised against this unit (open and historic).
    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    // Notice-to-vacate requests referencing this unit.
    public function moveOutRequests()
    {
        return $this->hasMany(MoveOutRequest::class);
    }

    // Coordinated turnover workspaces for this unit.
    public function turnovers()
    {
        return $this->hasMany(Turnover::class);
    }

    // The turnover currently in progress for this unit, if any.
    public function activeTurnover()
    {
        return $this->hasOne(Turnover::class)
            ->whereNotIn('status', [Turnover::STATUS_CANCELLED, Turnover::STATUS_OCCUPIED])
            ->latestOfMany();
    }

    // Historic security deposits held for this unit.
    public function securityDeposits()
    {
        return $this->hasMany(SecurityDeposit::class);
    }

    // A stable, human-readable identifier that does not change with the tenant.
    public function passportCode(): string
    {
        return 'KP-'.str_pad((string) $this->property_id, 3, '0', STR_PAD_LEFT)
            .'-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function isOccupied(): bool
    {
        return $this->status === 'occupied';
    }

    // The unit's operational status shown in the turnover workspace. When a
    // turnover is in progress its richer readiness status is surfaced; otherwise
    // the plain occupancy status is used, so no duplicate statuses are stored.
    public function operationalStatus(): array
    {
        $turnover = $this->relationLoaded('activeTurnover')
            ? $this->getRelation('activeTurnover')
            : $this->activeTurnover;

        if ($turnover) {
            return [
                'key' => $turnover->status,
                'label' => $turnover->statusLabel(),
                'badge' => $turnover->statusBadgeClass(),
            ];
        }

        return [
            'key' => $this->status,
            'label' => ucfirst((string) $this->status),
            'badge' => $this->statusBadgeClass(),
        ];
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'occupied' => 'kp-badge-danger',
            'reserved' => 'kp-badge-warning',
            'vacant' => 'kp-badge-success',
            default => 'kp-badge-muted',
        };
    }
}

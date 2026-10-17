<?php

namespace App\Policies;

use App\Models\UnitInspection;
use App\Models\User;

class UnitInspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord() || $user->isCaretaker() || $user->isSuperAdmin();
    }

    public function view(User $user, UnitInspection $inspection): bool
    {
        return $user->can('viewPassport', $inspection->unit);
    }

    /**
     * Landlords and the assigned caretaker may edit a draft inspection.
     * Submitted inspections are immutable factual records.
     */
    public function update(User $user, UnitInspection $inspection): bool
    {
        return ! $inspection->isSubmitted()
            && $user->can('managePassport', $inspection->unit);
    }

    public function submit(User $user, UnitInspection $inspection): bool
    {
        return ! $inspection->isSubmitted()
            && $user->can('managePassport', $inspection->unit);
    }

    // Only the tenant of the documented tenancy may acknowledge the report, and
    // only while it has not already been acknowledged.
    public function acknowledge(User $user, UnitInspection $inspection): bool
    {
        if (! $user->isTenant() || $inspection->isAcknowledged()) {
            return false;
        }

        return $inspection->tenant?->user_id === $user->id
            || $inspection->unit?->tenants()->where('user_id', $user->id)->exists();
    }
}

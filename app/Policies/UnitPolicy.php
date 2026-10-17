<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user, Property $property): bool
    {
        return $property->user_id === $user->id;
    }

    public function view(User $user, Unit $unit): bool
    {
        return $unit->property->user_id === $user->id;
    }

    public function create(User $user, Property $property): bool
    {
        return $property->user_id === $user->id;
    }

    public function update(User $user, Unit $unit): bool
    {
        return $unit->property->user_id === $user->id;
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $unit->property->user_id === $user->id;
    }

    // ============ Property Passport ============

    /**
     * Who may open a unit's Property Passport:
     *  - the owning landlord,
     *  - a caretaker assigned to the property,
     *  - a tenant with a current or historic tenancy of the unit,
     *  - a super admin acting in an oversight capacity.
     */
    public function viewPassport(User $user, Unit $unit): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($unit->property?->user_id === $user->id) {
            return true;
        }

        if ($user->isCaretaker() && $unit->property?->caretakers()->whereKey($user->id)->exists()) {
            return true;
        }

        if ($user->isTenant()) {
            return $unit->tenants()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Who may alter passport content (inspections, documents, assessments).
     * Tenants may only acknowledge; super admins act read-only here.
     */
    public function managePassport(User $user, Unit $unit): bool
    {
        if ($unit->property?->user_id === $user->id) {
            return true;
        }

        return $user->isCaretaker()
            && $unit->property?->caretakers()->whereKey($user->id)->exists();
    }

    /**
     * Only the owning landlord may record a subjective condition assessment.
     */
    public function assessCondition(User $user, Unit $unit): bool
    {
        return $unit->property?->user_id === $user->id;
    }
}

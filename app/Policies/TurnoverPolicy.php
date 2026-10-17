<?php

namespace App\Policies;

use App\Models\Turnover;
use App\Models\User;

class TurnoverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord() || $user->isCaretaker() || $user->isSuperAdmin();
    }

    public function view(User $user, Turnover $turnover): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isLandlord()) {
            return $turnover->property?->user_id === $user->id;
        }

        if ($user->isCaretaker()) {
            // A caretaker may only see turnovers for properties they manage or
            // where they have an assigned task.
            if ($turnover->property?->caretakers()->whereKey($user->id)->exists()) {
                return true;
            }

            return $turnover->tasks()->where('assigned_to', $user->id)->exists();
        }

        return false;
    }

    public function manage(User $user, Turnover $turnover): bool
    {
        return $user->isLandlord() && $turnover->property?->user_id === $user->id;
    }

    // Only the owning landlord may confirm readiness and begin onboarding.
    public function confirmReady(User $user, Turnover $turnover): bool
    {
        return $this->manage($user, $turnover);
    }

    public function onboard(User $user, Turnover $turnover): bool
    {
        return $this->manage($user, $turnover);
    }
}

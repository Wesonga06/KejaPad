<?php

namespace App\Policies;

use App\Models\PropertyAlert;
use App\Models\User;

class PropertyAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord() || $user->isCaretaker() || $user->isSuperAdmin();
    }

    public function view(User $user, PropertyAlert $alert): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isCaretaker()) {
            return $alert->property?->caretakers()->whereKey($user->id)->exists() ?? false;
        }

        return $user->isLandlord() && $alert->property?->user_id === $user->id;
    }

    // Acknowledging, dismissing and resolving are landlord decisions on their
    // own properties. Caretakers and other landlords may not act on them.
    public function manage(User $user, PropertyAlert $alert): bool
    {
        return $user->isLandlord() && $alert->property?->user_id === $user->id;
    }

    public function manageSettings(User $user, PropertyAlert $alert): bool
    {
        return $this->manage($user, $alert);
    }
}

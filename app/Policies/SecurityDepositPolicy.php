<?php

namespace App\Policies;

use App\Models\SecurityDeposit;
use App\Models\User;

class SecurityDepositPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    public function view(User $user, SecurityDeposit $deposit): bool
    {
        if ($user->isLandlord()) {
            return $deposit->property?->user_id === $user->id;
        }

        if ($user->isTenant()) {
            return $deposit->tenant?->user_id === $user->id;
        }

        return false;
    }

    public function manage(User $user, SecurityDeposit $deposit): bool
    {
        return $user->isLandlord() && $deposit->property?->user_id === $user->id;
    }
}

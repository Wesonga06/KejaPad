<?php

namespace App\Policies;

use App\Models\DepositSettlement;
use App\Models\User;

class DepositSettlementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord() || $user->isSuperAdmin();
    }

    public function view(User $user, DepositSettlement $settlement): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isLandlord()) {
            return $settlement->moveOutRequest?->property?->user_id === $user->id;
        }

        if ($user->isTenant()) {
            return $settlement->isVisibleToTenant()
                && $settlement->moveOutRequest?->tenant?->user_id === $user->id;
        }

        return false;
    }

    // Create and prepare the statement.
    public function create(User $user): bool
    {
        return $user->isLandlord();
    }

    public function manage(User $user, DepositSettlement $settlement): bool
    {
        return $user->isLandlord()
            && $settlement->moveOutRequest?->property?->user_id === $user->id;
    }

    // The tenant may only respond to their own, already-sent statement.
    public function respond(User $user, DepositSettlement $settlement): bool
    {
        return $user->isTenant()
            && $settlement->isVisibleToTenant()
            && ! $settlement->isSettled()
            && $settlement->moveOutRequest?->tenant?->user_id === $user->id;
    }
}

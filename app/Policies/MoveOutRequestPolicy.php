<?php

namespace App\Policies;

use App\Models\MoveOutRequest;
use App\Models\User;

class MoveOutRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    public function view(User $user, MoveOutRequest $moveOut): bool
    {
        if ($user->isLandlord()) {
            return $moveOut->property?->user_id === $user->id;
        }

        if ($user->isTenant()) {
            return $moveOut->tenant?->user_id === $user->id;
        }

        if ($user->isCaretaker()) {
            return $moveOut->inspections()->where('caretaker_id', $user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isTenant() && $user->tenantProfile()->exists();
    }

    public function review(User $user, MoveOutRequest $moveOut): bool
    {
        return $user->isLandlord() && $moveOut->property?->user_id === $user->id;
    }

    public function assignInspection(User $user, MoveOutRequest $moveOut): bool
    {
        return $user->isLandlord() && $moveOut->property?->user_id === $user->id;
    }

    public function clear(User $user, MoveOutRequest $moveOut): bool
    {
        return $user->isLandlord() && $moveOut->property?->user_id === $user->id;
    }

    public function cancel(User $user, MoveOutRequest $moveOut): bool
    {
        return $moveOut->isOpen() && $moveOut->tenant?->user_id === $user->id;
    }
}

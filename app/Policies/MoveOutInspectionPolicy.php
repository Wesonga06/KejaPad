<?php

namespace App\Policies;

use App\Models\MoveOutInspection;
use App\Models\User;

class MoveOutInspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCaretaker() || $user->isLandlord();
    }

    public function view(User $user, MoveOutInspection $inspection): bool
    {
        if ($user->isCaretaker()) {
            return $inspection->caretaker_id === $user->id;
        }

        if ($user->isLandlord()) {
            return $inspection->moveOutRequest?->property?->user_id === $user->id;
        }

        return false;
    }

    public function submit(User $user, MoveOutInspection $inspection): bool
    {
        return $user->isCaretaker()
            && $inspection->caretaker_id === $user->id
            && ! $inspection->isSubmitted();
    }
}

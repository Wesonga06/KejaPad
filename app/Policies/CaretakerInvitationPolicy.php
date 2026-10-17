<?php

namespace App\Policies;

use App\Models\CaretakerInvitation;
use App\Models\User;

class CaretakerInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    public function view(User $user, CaretakerInvitation $invitation): bool
    {
        return $user->isLandlord() && $invitation->landlord_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isLandlord();
    }

    public function revoke(User $user, CaretakerInvitation $invitation): bool
    {
        return $user->isLandlord() && $invitation->landlord_id === $user->id;
    }
}

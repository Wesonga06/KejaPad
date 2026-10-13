<?php

namespace App\Policies;

use App\Models\RentRecord;
use App\Models\User;

class RentRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    public function view(User $user, RentRecord $rentRecord): bool
    {
        return $rentRecord->property->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isLandlord();
    }

    public function update(User $user, RentRecord $rentRecord): bool
    {
        return $rentRecord->property->user_id === $user->id;
    }

    public function delete(User $user, RentRecord $rentRecord): bool
    {
        return $rentRecord->property->user_id === $user->id;
    }

    public function recordPayment(User $user, RentRecord $rentRecord): bool
    {
        return $rentRecord->property->user_id === $user->id;
    }

    public function markOverdue(User $user, RentRecord $rentRecord): bool
    {
        return $rentRecord->property->user_id === $user->id;
    }

    public function pay(User $user, RentRecord $rentRecord): bool
    {
        return $user->isTenant() && $user->tenantProfile?->id === $rentRecord->tenant_id;
    }
}

<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $tenant->property->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isLandlord();
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $tenant->property->user_id === $user->id;
    }

    public function delete(User $user, Tenant $tenant): bool
    {
        return $tenant->property->user_id === $user->id;
    }

    public function deactivate(User $user, Tenant $tenant): bool
    {
        return $tenant->property->user_id === $user->id;
    }
}

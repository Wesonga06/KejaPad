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
}

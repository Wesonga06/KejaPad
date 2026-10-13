<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $announcement->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isLandlord();
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $announcement->user_id === $user->id;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $announcement->user_id === $user->id;
    }

    public function togglePublish(User $user, Announcement $announcement): bool
    {
        return $announcement->user_id === $user->id;
    }
}

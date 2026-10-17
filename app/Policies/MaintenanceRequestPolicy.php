<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MaintenanceRequest $request): bool
    {
        if ($request->property->user_id === $user->id) {
            return true;
        }

        if ($request->assigned_to === $user->id) {
            return true;
        }

        if ($user->isCaretaker() && $request->property->caretakers()->whereKey($user->id)->exists()) {
            return true;
        }

        return $user->tenantProfile?->id === $request->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->isTenant();
    }

    public function update(User $user, MaintenanceRequest $request): bool
    {
        return $request->property->user_id === $user->id
            || $request->assigned_to === $user->id
            || ($user->isCaretaker() && $request->property->caretakers()->whereKey($user->id)->exists());
    }

    public function assign(User $user, MaintenanceRequest $request): bool
    {
        return $request->property->user_id === $user->id;
    }

    // Only the landlord who owns the property may verify or reject a completion.
    public function verify(User $user, MaintenanceRequest $request): bool
    {
        return $request->property->user_id === $user->id;
    }

    public function returnTask(User $user, MaintenanceRequest $request): bool
    {
        return $request->property->user_id === $user->id;
    }

    // Any authorised viewer (owner, assigned caretaker, property caretaker,
    // or the reporting tenant) may stream the task's supporting files.
    public function viewEvidence(User $user, MaintenanceRequest $request): bool
    {
        return $this->view($user, $request);
    }

    public function delete(User $user, MaintenanceRequest $request): bool
    {
        return $request->property->user_id === $user->id;
    }
}

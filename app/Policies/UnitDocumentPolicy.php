<?php

namespace App\Policies;

use App\Models\UnitDocument;
use App\Models\User;

class UnitDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord() || $user->isCaretaker() || $user->isSuperAdmin();
    }

    /**
     * Private documents are readable by:
     *  - the owning landlord and assigned caretakers,
     *  - a super admin in an oversight capacity,
     *  - the tenant, but only when the document is explicitly shared.
     */
    public function view(User $user, UnitDocument $document): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $unit = $document->unit;

        if ($unit === null) {
            return false;
        }

        if ($unit->property?->user_id === $user->id) {
            return true;
        }

        if ($user->isCaretaker() && $unit->property?->caretakers()->whereKey($user->id)->exists()) {
            return true;
        }

        return $user->isTenant()
            && $document->isTenantVisible()
            && $unit->tenants()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isLandlord() || $user->isCaretaker();
    }

    // The uploader, the owning landlord or the assigned caretaker may remove a
    // document. Historic evidence is never deleted silently by unrelated users.
    public function delete(User $user, UnitDocument $document): bool
    {
        if ($document->uploaded_by === $user->id) {
            return true;
        }

        return $user->can('managePassport', $document->unit);
    }
}

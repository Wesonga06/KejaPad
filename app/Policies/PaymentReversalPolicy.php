<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\User;

class PaymentReversalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, PaymentReversal $reversal): bool
    {
        return $user->isSuperAdmin()
            || ($user->isLandlord() && $user->id === $reversal->payment->tenant->property->user_id);
    }

    public function create(User $user, Payment $payment): bool
    {
        return $user->canAccessLandlordFeatures()
            && $payment->tenant->property->user_id === $user->id;
    }

    public function approve(User $user, PaymentReversal $reversal): bool
    {
        return $user->isSuperAdmin();
    }

    public function reject(User $user, PaymentReversal $reversal): bool
    {
        return $user->isSuperAdmin();
    }

    public function recordExternalRefund(User $user, PaymentReversal $reversal): bool
    {
        return $user->isSuperAdmin();
    }
}

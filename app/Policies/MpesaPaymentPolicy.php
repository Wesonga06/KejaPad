<?php

namespace App\Policies;

use App\Models\MpesaPayment;
use App\Models\User;

class MpesaPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLandlord();
    }

    public function view(User $user, MpesaPayment $payment): bool
    {
        if ($user->isLandlord()) {
            return $payment->tenant->property->user_id === $user->id;
        }

        return $user->tenantProfile?->id === $payment->tenant_id;
    }

    public function confirm(User $user, MpesaPayment $payment): bool
    {
        return $payment->tenant->property->user_id === $user->id;
    }

    public function reject(User $user, MpesaPayment $payment): bool
    {
        return $payment->tenant->property->user_id === $user->id;
    }
}

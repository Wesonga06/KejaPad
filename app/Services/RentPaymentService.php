<?php

namespace App\Services;

use App\Models\RentRecord;

class RentPaymentService
{
    public function __construct(
        protected RentAllocationService $allocation,
    ) {}

    /**
     * Apply a payment to a specific rent record.
     *
     * Retained for backwards compatibility; delegates to the allocation ledger so
     * every payment is preserved as an individual, auditable transaction.
     */
    public function apply(RentRecord $rent, float $amount, ?string $transactionCode = null, ?string $paymentDate = null): RentRecord
    {
        $this->allocation->recordPayment($rent->tenant, $amount, [
            'payment_date' => $paymentDate ?? now()->toDateString(),
            'reference' => $transactionCode,
            'method' => 'mpesa',
            'source' => 'tenant',
            'policy' => RentAllocationService::POLICY_MANUAL,
            'rent_record_id' => $rent->id,
        ]);

        return $rent->fresh();
    }
}

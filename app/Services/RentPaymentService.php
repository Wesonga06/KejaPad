<?php

namespace App\Services;

use App\Models\RentRecord;
use Illuminate\Support\Facades\DB;

class RentPaymentService
{
    public function apply(RentRecord $rent, float $amount, ?string $transactionCode = null, ?string $paymentDate = null): RentRecord
    {
        return DB::transaction(function () use ($rent, $amount, $transactionCode, $paymentDate) {
            $rent = RentRecord::whereKey($rent->id)->lockForUpdate()->firstOrFail();

            $amountDue = (float) $rent->amount_due;
            $totalPaid = (float) $rent->amount_paid + $amount;

            $rent->update([
                'amount_paid' => min($totalPaid, $amountDue),
                'payment_date' => $paymentDate ?? now()->toDateString(),
                'transaction_code' => $transactionCode ?? $rent->transaction_code,
                'status' => $totalPaid >= $amountDue ? 'paid' : 'partial',
            ]);

            return $rent;
        });
    }
}

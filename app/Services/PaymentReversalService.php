<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\PaymentReversalAllocation;
use App\Models\RentAudit;
use App\Models\RentRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentReversalService
{
    private RentAllocationService $allocationService;

    public function __construct(RentAllocationService $allocationService)
    {
        $this->allocationService = $allocationService;
    }

    public function initiate(Payment $payment, User $actor, float|string $amount, float|string $credit, string $reason, array $allocationAmounts): PaymentReversal
    {
        return DB::transaction(function () use ($payment, $actor, $amount, $credit, $reason, $allocationAmounts) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $payment->reversals()->lockForUpdate()->get();
            $amountCents = $this->toCents($amount);
            $creditCents = $this->toCents($credit);
            $splitCents = $creditCents + array_sum(array_map($this->toCents(...), $allocationAmounts));

            if ($amountCents <= 0 || $splitCents !== $amountCents) {
                throw ValidationException::withMessages(['amount' => 'The credit and rent allocation amounts must add up to the reversal amount.']);
            }

            $reservedTotal = (float) $payment->reversals()->whereIn('status', [PaymentReversal::STATUS_PENDING, PaymentReversal::STATUS_APPROVED])->sum('amount');
            if ($this->toCents($reservedTotal) + $amountCents > $this->toCents($payment->amount)) {
                throw ValidationException::withMessages(['amount' => 'The requested reversal exceeds the payment amount still available for reversal.']);
            }

            $pendingCredit = (float) $payment->reversals()->where('status', PaymentReversal::STATUS_PENDING)->sum('reversed_credit');
            if ($creditCents > max(0, $this->toCents($payment->unallocated_amount) - $this->toCents($pendingCredit))) {
                throw ValidationException::withMessages(['reversed_credit' => 'The requested held credit is no longer available.']);
            }

            $reversal = PaymentReversal::create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reversed_credit' => $credit,
                'reason' => $reason,
                'status' => PaymentReversal::STATUS_PENDING,
                'initiated_by' => $actor->id,
                'initiator_name' => $actor->name,
                'initiated_at' => now(),
                'provider_reference' => $payment->method === 'mpesa' ? $payment->reference : null,
                'external_refund_status' => $payment->method === 'mpesa' ? 'not_recorded' : 'not_applicable',
            ]);

            foreach ($allocationAmounts as $allocationId => $lineAmount) {
                $source = $payment->allocations()->whereKey($allocationId)->lockForUpdate()->first();
                if (! $source || $this->toCents($lineAmount) <= 0) {
                    throw ValidationException::withMessages(['allocations' => 'Each rent allocation must belong to this payment and have a positive amount.']);
                }

                $reserved = (float) PaymentReversalAllocation::where('payment_allocation_id', $source->id)
                    ->whereHas('reversal', fn ($query) => $query->whereIn('status', [PaymentReversal::STATUS_PENDING, PaymentReversal::STATUS_APPROVED]))->sum('amount');
                if ($this->toCents($reserved) + $this->toCents($lineAmount) > $this->toCents($source->amount)) {
                    throw ValidationException::withMessages(['allocations' => 'A requested rent allocation exceeds the amount available to reverse.']);
                }

                PaymentReversalAllocation::create([
                    'payment_reversal_id' => $reversal->id,
                    'payment_allocation_id' => $source->id,
                    'amount' => $lineAmount,
                ]);
            }

            RentAudit::record(RentAudit::ACTION_REVERSAL_INITIATED, $reversal, [
                'payment_id' => $payment->id,
                'amount' => $amount,
                'reversed_credit' => $credit,
                'reason' => $reason,
                'allocation_amounts' => $allocationAmounts,
            ], [], $actor->id);

            return $reversal->load('allocations.paymentAllocation.rentRecord', 'payment');
        }, 3);
    }

    public function decide(PaymentReversal $reversal, User $actor, bool $approve, ?string $reason = null): PaymentReversal
    {
        return DB::transaction(function () use ($reversal, $actor, $approve, $reason) {
            $payment = Payment::whereKey($reversal->payment_id)->lockForUpdate()->firstOrFail();
            $reversal = PaymentReversal::whereKey($reversal->id)->lockForUpdate()->firstOrFail();

            if ($reversal->payment_id !== $payment->id) {
                throw ValidationException::withMessages(['reversal' => 'The payment linked to this reversal changed. Reload and try again.']);
            }

            $reversal->load('allocations.paymentAllocation');

            if ($reversal->status !== PaymentReversal::STATUS_PENDING) {
                throw ValidationException::withMessages(['reversal' => 'This reversal has already been decided.']);
            }
            if ($approve && $reversal->initiated_by === $actor->id) {
                throw ValidationException::withMessages(['reversal' => 'The initiator cannot approve their own reversal.']);
            }

            if (! $approve) {
                $reversal->update(['status' => PaymentReversal::STATUS_REJECTED, 'rejected_by' => $actor->id, 'rejector_name' => $actor->name, 'rejection_reason' => $reason, 'rejected_at' => now()]);
                RentAudit::record(RentAudit::ACTION_REVERSAL_REJECTED, $reversal, ['reason' => $reason], [], $actor->id);

                return $reversal->fresh();
            }

            $pendingOrApproved = (float) $payment->reversals()->whereIn('status', [PaymentReversal::STATUS_PENDING, PaymentReversal::STATUS_APPROVED])->sum('amount');
            if ($this->toCents($pendingOrApproved) > $this->toCents($payment->amount) || $this->toCents($reversal->reversed_credit) > $this->toCents($payment->unallocated_amount)) {
                throw ValidationException::withMessages(['reversal' => 'Available payment balances changed; this reversal cannot be approved.']);
            }

            $rentIds = [];
            foreach ($reversal->allocations as $line) {
                $source = $payment->allocations()->whereKey($line->payment_allocation_id)->lockForUpdate()->firstOrFail();
                $reserved = (float) PaymentReversalAllocation::where('payment_allocation_id', $source->id)
                    ->whereHas('reversal', fn ($query) => $query->whereIn('status', [PaymentReversal::STATUS_PENDING, PaymentReversal::STATUS_APPROVED]))->sum('amount');
                if ($this->toCents($reserved) > $this->toCents($source->amount)) {
                    throw ValidationException::withMessages(['reversal' => 'Available rent allocation changed; this reversal cannot be approved.']);
                }
                $rentIds[] = $source->rent_record_id;
            }

            $payment->update(['unallocated_amount' => ($this->toCents($payment->unallocated_amount) - $this->toCents($reversal->reversed_credit)) / 100]);
            $reversal->update(['status' => PaymentReversal::STATUS_APPROVED, 'approved_by' => $actor->id, 'approver_name' => $actor->name, 'approved_at' => now()]);
            foreach (array_unique($rentIds) as $rentId) {
                $rent = RentRecord::whereKey($rentId)->lockForUpdate()->first();
                if ($rent) {
                    $this->allocationService->syncRecord($rent);
                }
            }
            RentAudit::record(RentAudit::ACTION_REVERSAL_APPROVED, $reversal, ['amount' => $reversal->amount, 'reversed_credit' => $reversal->reversed_credit], [], $actor->id);

            return $reversal->fresh(['payment', 'allocations.paymentAllocation.rentRecord']);
        }, 3);
    }

    public function recordExternalRefund(PaymentReversal $reversal, User $actor, string $status, ?string $reference): PaymentReversal
    {
        return DB::transaction(function () use ($reversal, $actor, $status, $reference) {
            Payment::whereKey($reversal->payment_id)->lockForUpdate()->firstOrFail();
            $locked = PaymentReversal::whereKey($reversal->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== PaymentReversal::STATUS_APPROVED) {
                throw ValidationException::withMessages(['status' => 'Only an approved accounting reversal can have an external refund recorded.']);
            }
            if ($locked->payment->method !== 'mpesa') {
                throw ValidationException::withMessages(['status' => 'External M-Pesa refunds can only be recorded for M-Pesa payments.']);
            }
            $old = ['status' => $locked->external_refund_status, 'reference' => $locked->external_refund_reference];
            $locked->update(['external_refund_status' => $status, 'external_refund_reference' => $reference, 'external_refund_confirmed_at' => $status === 'confirmed' ? now() : null]);
            RentAudit::record(RentAudit::ACTION_EXTERNAL_REFUND_UPDATED, $locked, ['status' => $status, 'reference' => $reference], $old, $actor->id);

            return $locked->fresh();
        }, 3);
    }

    private function toCents(float|string|int $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}

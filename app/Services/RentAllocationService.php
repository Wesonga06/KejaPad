<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentReversal;
use App\Models\PaymentReversalAllocation;
use App\Models\RentAudit;
use App\Models\RentRecord;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RentAllocationService
{
    public const POLICY_OLDEST = 'oldest';

    public const POLICY_CURRENT = 'current';

    public const POLICY_MANUAL = 'manual';

    /**
     * Record a payment transaction and allocate it across outstanding rent periods.
     *
     * @param  array{
     *     payment_date?: string|\DateTimeInterface|null,
     *     reference?: ?string,
     *     method?: string,
     *     source?: string,
     *     recorded_by?: ?int,
     *     policy?: ?string,
     *     rent_record_id?: ?int,
     *     allocations?: ?array<int, array{rent_record_id: int, amount: float|int}>,
     *     notes?: ?string,
     *     mpesa_payment_id?: ?int,
     * }  $options
     */
    public function recordPayment(Tenant $tenant, float $amount, array $options = []): Payment
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'The payment amount must be greater than zero.',
            ]);
        }

        $reference = $options['reference'] ?? null;

        if ($reference !== null && Payment::where('reference', $reference)->exists()) {
            throw ValidationException::withMessages([
                'reference' => 'A payment with this reference has already been recorded.',
            ]);
        }

        // Idempotency for M-Pesa: a confirmed transaction must only ever credit once.
        if (! empty($options['mpesa_payment_id'])) {
            $existing = Payment::where('mpesa_payment_id', $options['mpesa_payment_id'])->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($tenant, $amount, $options, $reference) {
            $payment = Payment::create([
                'tenant_id' => $tenant->id,
                'amount' => $amount,
                'unallocated_amount' => $amount,
                'payment_date' => $options['payment_date'] ?? now()->toDateString(),
                'reference' => $reference,
                'method' => $options['method'] ?? 'cash',
                'source' => $options['source'] ?? 'landlord',
                'mpesa_payment_id' => $options['mpesa_payment_id'] ?? null,
                'recorded_by' => $options['recorded_by'] ?? auth()->id(),
                'notes' => $options['notes'] ?? null,
            ]);

            $remaining = $this->allocate($payment, $options);
            $payment->update(['unallocated_amount' => round($remaining, 2)]);

            RentAudit::record(RentAudit::ACTION_PAYMENT_RECORDED, $payment, [
                'tenant_id' => $tenant->id,
                'amount' => $amount,
                'unallocated_amount' => round($remaining, 2),
                'reference' => $reference,
                'method' => $payment->method,
            ]);

            return $payment->fresh(['allocations.rentRecord']);
        }, 3);
    }

    /**
     * Apply any unallocated payment credit on the tenant's account to outstanding
     * rent periods (oldest first). Used after new rent is generated so an overpayment
     * is automatically deducted from the next month's rent. Returns the amount applied.
     */
    public function applyTenantCredit(Tenant $tenant): float
    {
        return DB::transaction(function () use ($tenant) {
            $applied = 0.0;

            $payments = Payment::where('tenant_id', $tenant->id)
                ->where('unallocated_amount', '>', 0)
                ->orderBy('payment_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                $reserved = (float) $payment->reversals()->where('status', PaymentReversal::STATUS_PENDING)->sum('reversed_credit');
                $remaining = max(0, (float) $payment->unallocated_amount - $reserved);
                $availableBefore = $remaining;

                if ($remaining <= 0) {
                    continue;
                }

                // Lock each rent row before reading its balance. Other allocation
                // paths serialize on this row, so a held credit cannot use a stale
                // amount_paid value and over-allocate a period.
                $targetIds = RentRecord::where('tenant_id', $tenant->id)
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->pluck('id');

                foreach ($targetIds as $rentId) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $rent = RentRecord::whereKey($rentId)->lockForUpdate()->first();

                    if (! $rent || $rent->tenant_id !== $tenant->id) {
                        continue;
                    }

                    $balance = $rent->outstanding();

                    if ($balance <= 0) {
                        continue;
                    }

                    $take = min($remaining, $balance);
                    $this->applyAllocation($payment, $rent, $take);

                    $remaining -= $take;
                    $applied += $take;
                }

                if (round($remaining, 2) !== round($availableBefore, 2)) {
                    $unallocatedAfter = round($reserved + max(0.0, $remaining), 2);
                    $payment->update(['unallocated_amount' => $unallocatedAfter]);

                    RentAudit::record(RentAudit::ACTION_CREDIT_APPLIED, $payment, [
                        'tenant_id' => $tenant->id,
                        'credit_applied' => round($availableBefore - $remaining, 2),
                        'unallocated_amount' => $unallocatedAfter,
                    ]);
                }
            }

            return round($applied, 2);
        }, 3);
    }

    /**
     * Re-apply an existing payment to a new set of rent periods. Traceable.
     *
     * @param  array<int, array{rent_record_id: int, amount: float|int}>  $newAllocations
     */
    public function reallocate(Payment $payment, array $newAllocations, ?int $userId = null): Payment
    {
        return DB::transaction(function () use ($payment, $newAllocations, $userId) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->reversals()->exists()) {
                throw ValidationException::withMessages(['payment' => 'This payment has reversal history and can no longer be reallocated.']);
            }

            $old = $payment->allocations()->get(['rent_record_id', 'amount'])->toArray();

            $affected = $payment->allocations()->pluck('rent_record_id')
                ->merge(collect($newAllocations)->pluck('rent_record_id'))
                ->unique()
                ->filter()
                ->values();

            $payment->allocations()->delete();

            $remaining = (float) $payment->amount;

            foreach ($newAllocations as $row) {
                if ($remaining <= 0) {
                    break;
                }

                $rent = RentRecord::whereKey($row['rent_record_id'])->lockForUpdate()->first();

                if (! $rent || $rent->tenant_id !== $payment->tenant_id) {
                    continue;
                }

                $take = min((float) $row['amount'], $remaining, max(0.0, $rent->outstanding()));

                if ($take <= 0) {
                    continue;
                }

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'rent_record_id' => $rent->id,
                    'amount' => $take,
                ]);

                $remaining -= $take;
            }

            $payment->update(['unallocated_amount' => round(max(0.0, $remaining), 2)]);

            foreach ($affected as $rentId) {
                $rent = RentRecord::find($rentId);

                if ($rent) {
                    $this->syncRecord($rent);
                }
            }

            RentAudit::record(
                RentAudit::ACTION_PAYMENT_REALLOCATED,
                $payment,
                ['allocations' => $payment->allocations()->get(['rent_record_id', 'amount'])->toArray()],
                ['allocations' => $old],
                $userId,
            );

            return $payment->fresh(['allocations.rentRecord']);
        }, 3);
    }

    /**
     * Distribute a payment's amount across outstanding periods. Returns unallocated remainder.
     *
     * @param  array<string, mixed>  $options
     */
    protected function allocate(Payment $payment, array $options): float
    {
        $targets = $this->resolveTargets($payment, $options);
        $remaining = (float) $payment->amount;

        foreach ($targets as $target) {
            if ($remaining <= 0) {
                break;
            }

            $rent = RentRecord::whereKey($target['rent_record_id'])->lockForUpdate()->first();

            if (! $rent || $rent->tenant_id !== $payment->tenant_id) {
                continue;
            }

            $balance = $rent->outstanding();

            if ($balance <= 0) {
                continue;
            }

            $cap = array_key_exists('amount', $target) ? (float) $target['amount'] : null;
            $take = $cap !== null ? min($remaining, $cap, $balance) : min($remaining, $balance);

            if ($take <= 0) {
                continue;
            }

            $this->applyAllocation($payment, $rent, $take);
            $remaining -= $take;
        }

        return max(0.0, $remaining);
    }

    protected function applyAllocation(Payment $payment, RentRecord $rent, float $amount): void
    {
        $allocation = PaymentAllocation::firstOrNew([
            'payment_id' => $payment->id,
            'rent_record_id' => $rent->id,
        ]);

        $allocation->amount = (float) ($allocation->amount ?? 0) + $amount;
        $allocation->save();

        $this->syncRecord($rent);
    }

    /**
     * Recompute a rent record's cached amount_paid + status from its allocations.
     * Amounts are never capped below the true paid total; status is derived from data.
     */
    public function syncRecord(RentRecord $rent): RentRecord
    {
        $grossPaid = (float) $rent->allocations()->sum('amount');
        $reversed = (float) PaymentReversalAllocation::whereHas('reversal', fn ($query) => $query->where('status', PaymentReversal::STATUS_APPROVED))
            ->whereHas('paymentAllocation', fn ($query) => $query->where('rent_record_id', $rent->id))
            ->sum('amount');
        $paid = max(0, $grossPaid - $reversed);
        $rent->amount_paid = min($paid, (float) $rent->amount_due);

        $rent->status = $this->deriveStatus($rent);

        $latest = $rent->allocations()
            ->with('payment')
            ->get()
            ->sortByDesc(fn (PaymentAllocation $a) => $a->payment?->payment_date?->timestamp ?? 0)
            ->first();

        if ($latest?->payment) {
            $rent->payment_date = $latest->payment->payment_date;

            if ($latest->payment->reference) {
                $rent->transaction_code = $latest->payment->reference;
            }
        }

        $rent->save();

        return $rent;
    }

    protected function deriveStatus(RentRecord $rent): string
    {
        $due = (float) $rent->amount_due;
        $paid = (float) $rent->amount_paid;

        if ($paid >= $due && $due > 0) {
            return 'paid';
        }

        if ($paid > 0) {
            // An underpaid period that is past its due date stays overdue — a partial
            // payment must never silently clear an overdue account.
            return $rent->isOverdue() ? 'overdue' : 'partial';
        }

        return $rent->isOverdue() ? 'overdue' : 'pending';
    }

    /**
     * Recalculate every rent record for a tenant from the payment ledger.
     */
    public function recalculateTenant(Tenant $tenant): void
    {
        foreach ($tenant->rentRecords()->get() as $rent) {
            $this->syncRecord($rent);
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<int, array{rent_record_id: int, amount?: float}>
     */
    protected function resolveTargets(Payment $payment, array $options): array
    {
        // Explicit split allocation list (manual).
        if (! empty($options['allocations']) && is_array($options['allocations'])) {
            return array_values(array_map(fn ($a) => [
                'rent_record_id' => (int) $a['rent_record_id'],
                'amount' => (float) $a['amount'],
            ], $options['allocations']));
        }

        // Single manually-selected period.
        if (! empty($options['rent_record_id'])) {
            return [['rent_record_id' => (int) $options['rent_record_id']]];
        }

        $policy = $options['policy'] ?? config('rent.allocation_policy', self::POLICY_OLDEST);

        $outstanding = RentRecord::where('tenant_id', $payment->tenant_id)
            ->get()
            ->filter(fn (RentRecord $r) => $r->outstanding() > 0);

        $sorted = $outstanding->sort(function (RentRecord $a, RentRecord $b) use ($policy) {
            $aDate = $a->due_date?->timestamp ?? 0;
            $bDate = $b->due_date?->timestamp ?? 0;

            if ($aDate === $bDate) {
                return $policy === self::POLICY_CURRENT
                    ? $b->id <=> $a->id
                    : $a->id <=> $b->id;
            }

            return $policy === self::POLICY_CURRENT
                ? $bDate <=> $aDate
                : $aDate <=> $bDate;
        });

        return $sorted->map(fn (RentRecord $r) => ['rent_record_id' => $r->id])->values()->all();
    }
}

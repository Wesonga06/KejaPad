<?php

namespace App\Services;

use App\Models\DepositTransaction;
use App\Models\SecurityDeposit;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\DepositRefundRecordedNotification;
use Illuminate\Support\Facades\DB;

class DepositService
{
    // Number of months' rent held as a deposit, from configuration.
    public function policyMonths(): float
    {
        return (float) config('tenancy.deposit_months', 1);
    }

    // The required deposit for a given monthly rent.
    public function requiredAmount(float $monthlyRent): float
    {
        return round($monthlyRent * $this->policyMonths(), 2);
    }

    // Create the deposit obligation for a tenant if one does not already exist.
    public function ensureForTenant(Tenant $tenant, ?float $monthlyRent = null): SecurityDeposit
    {
        $existing = $tenant->securityDeposit()->first();

        if ($existing) {
            return $existing;
        }

        $rent = $monthlyRent ?? (float) ($tenant->unit?->rent_amount ?? 0);

        return SecurityDeposit::create([
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'unit_id' => $tenant->unit_id,
            'required_amount' => $this->requiredAmount($rent),
            'received_amount' => 0,
            'policy_months' => $this->policyMonths(),
            'status' => SecurityDeposit::STATUS_PENDING,
        ]);
    }

    // Create deposit obligations for any of a landlord's active tenants that
    // do not yet have one (e.g. tenancies created before deposits were tracked).
    // Returns the number of deposits created.
    public function ensureForLandlord(User $landlord): int
    {
        $created = 0;

        Tenant::where('is_active', true)
            ->whereHas('property', fn ($q) => $q->where('user_id', $landlord->id))
            ->doesntHave('securityDeposit')
            ->with('unit')
            ->chunkById(100, function ($tenants) use (&$created) {
                foreach ($tenants as $tenant) {
                    $this->ensureForTenant($tenant, (float) ($tenant->unit?->rent_amount ?? 0));
                    $created++;
                }
            });

        return $created;
    }

    // A tenancy may only be moved in once the deposit is fully received and
    // verified, unless an authorized exception has been recorded.
    public function isMoveInEligible(SecurityDeposit $deposit): bool
    {
        if ($deposit->hasException()) {
            return true;
        }

        return $deposit->isSatisfied() && $deposit->isVerified();
    }

    // Record an authorized exception that waives the outstanding deposit
    // requirement for move-in, together with its reason and approver.
    public function recordException(SecurityDeposit $deposit, string $reason, User $approvedBy): SecurityDeposit
    {
        if (blank($reason)) {
            abort(422, 'An authorized exception requires a reason.');
        }

        return DB::transaction(function () use ($deposit, $reason, $approvedBy) {
            $deposit = $this->lockDeposit($deposit);
            $deposit->update([
                'exception_reason' => $reason,
                'exception_approved_at' => now(),
                'exception_approved_by' => $approvedBy->id,
            ]);

            $this->syncUnitOccupancy($deposit);

            return $deposit->fresh();
        });
    }

    // Recalculate the received amount + persisted status from the receipts ledger.
    public function recompute(SecurityDeposit $deposit): SecurityDeposit
    {
        return DB::transaction(function () use ($deposit) {
            $deposit = $this->lockDeposit($deposit);
            $totals = $this->lockedFinancialTotals($deposit);

            $deposit->received_amount = round($totals['received'], 2);
            $deposit->status = $this->derivedStatus($deposit, $totals);
            $deposit->save();

            return $deposit;
        });
    }

    /**
     * Read the current ledger while holding the deposit mutex. Callers that
     * mutate deposit transactions must lock the deposit before using this.
     *
     * @return array{received: float, approved_deductions: float, completed_refunds: float, reserved_refunds: float}
     */
    public function lockedFinancialTotals(SecurityDeposit $deposit): array
    {
        $transactions = $deposit->transactions()
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['type', 'status', 'amount']);
        $receipts = $transactions->where('type', DepositTransaction::TYPE_RECEIPT);

        return [
            // Preserve deposits created before receipt transactions were
            // introduced; their received_amount remains the stored ledger value.
            'received' => $receipts->isEmpty()
                ? (float) $deposit->received_amount
                : (float) $receipts
                    ->where('status', '!=', DepositTransaction::STATUS_REVERSED)
                    ->sum('amount'),
            'approved_deductions' => (float) $transactions
                ->where('type', DepositTransaction::TYPE_DEDUCTION)
                ->whereIn('status', [DepositTransaction::STATUS_APPROVED, DepositTransaction::STATUS_COMPLETED])
                ->sum('amount'),
            'completed_refunds' => (float) $transactions
                ->where('type', DepositTransaction::TYPE_REFUND)
                ->where('status', DepositTransaction::STATUS_COMPLETED)
                ->sum('amount'),
            'reserved_refunds' => (float) $transactions
                ->where('type', DepositTransaction::TYPE_REFUND)
                ->whereIn('status', [DepositTransaction::STATUS_PROPOSED, DepositTransaction::STATUS_APPROVED])
                ->sum('amount'),
        ];
    }

    /**
     * @param  array{received: float, approved_deductions: float, completed_refunds: float, reserved_refunds: float}  $totals
     */
    protected function derivedStatus(SecurityDeposit $deposit, array $totals): string
    {
        $netRefundable = max(0.0, $totals['received'] - $totals['approved_deductions'] - $totals['completed_refunds']);

        if ($totals['completed_refunds'] > 0) {
            return $netRefundable > 0
                ? SecurityDeposit::STATUS_PARTIALLY_REFUNDED
                : SecurityDeposit::STATUS_REFUNDED;
        }

        if ($totals['received'] >= (float) $deposit->required_amount) {
            return $deposit->isVerified()
                ? SecurityDeposit::STATUS_VERIFIED
                : SecurityDeposit::STATUS_RECEIVED;
        }

        return $totals['received'] > 0
            ? SecurityDeposit::STATUS_PARTIAL
            : SecurityDeposit::STATUS_PENDING;
    }

    protected function lockDeposit(SecurityDeposit|int $deposit): SecurityDeposit
    {
        return SecurityDeposit::query()
            ->whereKey($deposit instanceof SecurityDeposit ? $deposit->id : $deposit)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  array{received: float, approved_deductions: float, completed_refunds: float, reserved_refunds: float}  $totals
     */
    protected function availableRefundFromTotals(array $totals): float
    {
        return max(0.0, $totals['received'] - $totals['approved_deductions']
            - $totals['completed_refunds'] - $totals['reserved_refunds']);
    }

    /**
     * @param  array{received: float, approved_deductions: float, completed_refunds: float, reserved_refunds: float}  $totals
     */
    protected function assertNoOvercommit(array $totals): void
    {
        $committed = $totals['approved_deductions'] + $totals['completed_refunds'] + $totals['reserved_refunds'];

        if (round($committed, 2) > round($totals['received'], 2)) {
            abort(422, 'Approved deductions and refunds cannot exceed the deposit received.');
        }
    }

    // Record a deposit receipt (money received from the tenant).
    public function recordReceipt(SecurityDeposit $deposit, array $data, User $recordedBy): DepositTransaction
    {
        return DB::transaction(function () use ($deposit, $data, $recordedBy) {
            $deposit = $this->lockDeposit($deposit);
            $transaction = $deposit->transactions()->create([
                'type' => DepositTransaction::TYPE_RECEIPT,
                'amount' => round((float) $data['amount'], 2),
                'method' => $data['method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => ! empty($data['verify']) ? DepositTransaction::STATUS_VERIFIED : DepositTransaction::STATUS_RECORDED,
                'recorded_by' => $recordedBy->id,
                'verified_at' => ! empty($data['verify']) ? now() : null,
                'verified_by' => ! empty($data['verify']) ? $recordedBy->id : null,
            ]);

            $this->recompute($deposit);

            return $transaction;
        });
    }

    public function verifyReceipt(DepositTransaction $transaction, User $verifiedBy): DepositTransaction
    {
        return DB::transaction(function () use ($transaction, $verifiedBy) {
            $deposit = SecurityDeposit::query()
                ->whereKey($transaction->security_deposit_id)
                ->lockForUpdate()
                ->firstOrFail();
            $transaction = DepositTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->type !== DepositTransaction::TYPE_RECEIPT) {
                abort(422, 'Only deposit receipts can be verified.');
            }

            if ($transaction->status === DepositTransaction::STATUS_VERIFIED) {
                return $transaction;
            }

            if ($transaction->status !== DepositTransaction::STATUS_RECORDED) {
                abort(422, 'Only recorded receipts can be verified.');
            }

            $transaction->update([
                'status' => DepositTransaction::STATUS_VERIFIED,
                'verified_at' => now(),
                'verified_by' => $verifiedBy->id,
            ]);

            $this->recompute($deposit);

            return $transaction;
        });
    }

    // Mark the whole deposit verified once the required amount has been received.
    public function verifyDeposit(SecurityDeposit $deposit, User $verifiedBy): SecurityDeposit
    {
        return DB::transaction(function () use ($deposit, $verifiedBy) {
            $deposit = $this->lockDeposit($deposit);
            $totals = $this->lockedFinancialTotals($deposit);

            if ($totals['received'] < (float) $deposit->required_amount) {
                abort(422, 'The deposit is not fully received yet.');
            }

            $deposit->update([
                'verified_at' => now(),
                'verified_by' => $verifiedBy->id,
            ]);

            $this->recompute($deposit);
            $this->syncUnitOccupancy($deposit->fresh());

            return $deposit->fresh();
        });
    }

    // When a deposit is verified for an active tenant whose unit is reserved
    // pending the deposit, complete the move-in by marking the unit occupied.
    public function syncUnitOccupancy(SecurityDeposit $deposit): void
    {
        if (! config('tenancy.require_deposit_before_move_in', true)) {
            return;
        }

        $tenant = $deposit->tenant;

        if (! $tenant || ! $tenant->is_active) {
            return;
        }

        $unit = $deposit->unit;

        if ($unit && $unit->status === 'reserved' && $this->isMoveInEligible($deposit)) {
            $unit->update(['status' => 'occupied']);

            // The incoming tenancy has now moved in; complete any turnover that
            // was awaiting this unit.
            app(TurnoverService::class)->completeIncomingTenancy($unit->fresh());
        }
    }

    // Proposed deduction against the deposit (requires review before it counts).
    public function proposeDeduction(SecurityDeposit $deposit, array $data, User $recordedBy): DepositTransaction
    {
        return DB::transaction(function () use ($deposit, $data, $recordedBy) {
            $deposit = $this->lockDeposit($deposit);
            $totals = $this->lockedFinancialTotals($deposit);

            if ((float) $data['amount'] > $totals['received']) {
                abort(422, 'A deduction cannot exceed the deposit received.');
            }

            $transaction = $deposit->transactions()->create([
                'type' => DepositTransaction::TYPE_DEDUCTION,
                'amount' => round((float) $data['amount'], 2),
                'status' => DepositTransaction::STATUS_PROPOSED,
                'reason' => $data['reason'] ?? null,
                'evidence' => $data['evidence'] ?? null,
                'move_out_request_id' => $data['move_out_request_id'] ?? null,
                'recorded_by' => $recordedBy->id,
            ]);

            $this->recompute($deposit);

            return $transaction;
        });
    }

    public function approveDeduction(DepositTransaction $transaction, User $approvedBy): DepositTransaction
    {
        return DB::transaction(function () use ($transaction, $approvedBy) {
            $deposit = SecurityDeposit::query()
                ->whereKey($transaction->security_deposit_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedTransaction = DepositTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->type !== DepositTransaction::TYPE_DEDUCTION) {
                abort(422, 'Only deductions can be approved.');
            }

            if ($lockedTransaction->status !== DepositTransaction::STATUS_PROPOSED) {
                abort(422, 'Only proposed deductions can be approved.');
            }

            $totals = $this->lockedFinancialTotals($deposit);
            $totals['approved_deductions'] += (float) $lockedTransaction->amount;

            $this->assertNoOvercommit($totals);

            $lockedTransaction->update([
                'status' => DepositTransaction::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $approvedBy->id,
            ]);

            $this->recompute($deposit);

            return $lockedTransaction->fresh();
        });
    }

    // Record an actual deposit refund payout to the tenant.
    public function recordRefund(SecurityDeposit $deposit, array $data, User $recordedBy): DepositTransaction
    {
        return DB::transaction(function () use ($deposit, $data, $recordedBy) {
            $deposit = $this->lockDeposit($deposit);
            $totals = $this->lockedFinancialTotals($deposit);
            $available = $this->availableRefundFromTotals($totals);

            if ((float) $data['amount'] > $available) {
                abort(422, 'Refund exceeds the available refundable balance.');
            }

            $this->assertReferenceNotAlreadyRefunded($deposit, $data['reference'] ?? null);

            $transaction = $deposit->transactions()->create([
                'type' => DepositTransaction::TYPE_REFUND,
                'amount' => round((float) $data['amount'], 2),
                'method' => $data['method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'status' => DepositTransaction::STATUS_COMPLETED,
                'move_out_request_id' => $data['move_out_request_id'] ?? null,
                'recorded_by' => $recordedBy->id,
                'approved_by' => $recordedBy->id,
                'approved_at' => now(),
                'completed_at' => now(),
            ]);

            $this->recompute($deposit);

            if ($deposit->tenant?->user) {
                $deposit->tenant->user->notify(
                    new DepositRefundRecordedNotification($deposit, $transaction)
                );
            }

            return $transaction;
        });
    }

    // A refund that has been proposed but not yet approved or paid. Proposing
    // reserves the balance so the same funds cannot be promised twice.
    public function proposeRefund(SecurityDeposit $deposit, array $data, User $recordedBy): DepositTransaction
    {
        return DB::transaction(function () use ($deposit, $data, $recordedBy) {
            $deposit = $this->lockDeposit($deposit);
            $totals = $this->lockedFinancialTotals($deposit);

            if ((float) $data['amount'] > $this->availableRefundFromTotals($totals)) {
                abort(422, 'Refund exceeds the available refundable balance.');
            }

            return $deposit->transactions()->create([
                'type' => DepositTransaction::TYPE_REFUND,
                'amount' => round((float) $data['amount'], 2),
                'status' => DepositTransaction::STATUS_PROPOSED,
                'reason' => $data['reason'] ?? null,
                'move_out_request_id' => $data['move_out_request_id'] ?? null,
                'recorded_by' => $recordedBy->id,
            ]);
        });
    }

    public function approveRefund(DepositTransaction $transaction, User $approvedBy): DepositTransaction
    {
        return DB::transaction(function () use ($transaction, $approvedBy) {
            $deposit = $this->lockDeposit($transaction->security_deposit_id);
            $transaction = DepositTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->type !== DepositTransaction::TYPE_REFUND) {
                abort(422, 'Only refunds can be approved.');
            }

            if ($transaction->status !== DepositTransaction::STATUS_PROPOSED) {
                abort(422, 'Only a proposed refund can be approved.');
            }

            $this->assertNoOvercommit($this->lockedFinancialTotals($deposit));

            $transaction->update([
                'status' => DepositTransaction::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $approvedBy->id,
            ]);

            return $transaction->fresh();
        });
    }

    // Complete an approved refund once payment has actually been made.
    public function completeRefund(DepositTransaction $transaction, array $data, User $recordedBy): DepositTransaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $deposit = $this->lockDeposit($transaction->security_deposit_id);
            $transaction = DepositTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaction->type !== DepositTransaction::TYPE_REFUND) {
                abort(422, 'Only refunds can be completed.');
            }

            if ($transaction->status === DepositTransaction::STATUS_COMPLETED) {
                abort(422, 'This refund has already been completed.');
            }

            if ($transaction->status !== DepositTransaction::STATUS_APPROVED) {
                abort(422, 'A refund must be approved before it can be completed.');
            }

            $this->assertNoOvercommit($this->lockedFinancialTotals($deposit));

            $this->assertReferenceNotAlreadyRefunded($deposit, $data['reference'] ?? null, $transaction->id);

            $transaction->update([
                'status' => DepositTransaction::STATUS_COMPLETED,
                'method' => $data['method'] ?? $transaction->method,
                'reference' => $data['reference'] ?? $transaction->reference,
                'completed_at' => now(),
            ]);

            $this->recompute($deposit);

            if ($deposit->tenant?->user) {
                $deposit->tenant->user->notify(
                    new DepositRefundRecordedNotification($deposit, $transaction->fresh())
                );
            }

            return $transaction->fresh();
        });
    }

    // Guard against recording the same external payment reference twice.
    protected function assertReferenceNotAlreadyRefunded(SecurityDeposit $deposit, ?string $reference, ?int $exceptId = null): void
    {
        if (blank($reference)) {
            return;
        }

        $exists = $deposit->refunds()
            ->where('status', DepositTransaction::STATUS_COMPLETED)
            ->where('reference', $reference)
            ->when($exceptId !== null, fn ($q) => $q->whereKeyNot($exceptId))
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            abort(422, 'A refund with this reference has already been recorded.');
        }
    }
}

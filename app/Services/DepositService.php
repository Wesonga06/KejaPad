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
        $received = (float) $deposit->receipts()
            ->where('status', '!=', DepositTransaction::STATUS_REVERSED)
            ->sum('amount');

        $deposit->received_amount = round($received, 2);
        $deposit->status = $deposit->derivedStatus();
        $deposit->save();

        return $deposit;
    }

    // Record a deposit receipt (money received from the tenant).
    public function recordReceipt(SecurityDeposit $deposit, array $data, User $recordedBy): DepositTransaction
    {
        return DB::transaction(function () use ($deposit, $data, $recordedBy) {
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
            if ($transaction->type !== DepositTransaction::TYPE_RECEIPT) {
                abort(422, 'Only deposit receipts can be verified.');
            }

            $transaction->update([
                'status' => DepositTransaction::STATUS_VERIFIED,
                'verified_at' => now(),
                'verified_by' => $verifiedBy->id,
            ]);

            $this->recompute($transaction->securityDeposit);

            return $transaction;
        });
    }

    // Mark the whole deposit verified once the required amount has been received.
    public function verifyDeposit(SecurityDeposit $deposit, User $verifiedBy): SecurityDeposit
    {
        if (! $deposit->isSatisfied()) {
            abort(422, 'The deposit is not fully received yet.');
        }

        $deposit->update([
            'verified_at' => now(),
            'verified_by' => $verifiedBy->id,
        ]);

        $this->recompute($deposit);
        $this->syncUnitOccupancy($deposit);

        return $deposit;
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
        }
    }

    // Proposed deduction against the deposit (requires review before it counts).
    public function proposeDeduction(SecurityDeposit $deposit, array $data, User $recordedBy): DepositTransaction
    {
        if ((float) $data['amount'] > (float) $deposit->received_amount) {
            abort(422, 'A deduction cannot exceed the deposit received.');
        }

        return DB::transaction(function () use ($deposit, $data, $recordedBy) {
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
        if ($transaction->type !== DepositTransaction::TYPE_DEDUCTION) {
            abort(422, 'Only deductions can be approved.');
        }

        $transaction->update([
            'status' => DepositTransaction::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy->id,
        ]);

        $this->recompute($transaction->securityDeposit);

        return $transaction;
    }

    // Record an actual deposit refund payout to the tenant.
    public function recordRefund(SecurityDeposit $deposit, array $data, User $recordedBy): DepositTransaction
    {
        $available = $deposit->netRefundable();

        if ((float) $data['amount'] > $available) {
            abort(422, 'Refund exceeds the net refundable balance.');
        }

        return DB::transaction(function () use ($deposit, $data, $recordedBy) {
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
}

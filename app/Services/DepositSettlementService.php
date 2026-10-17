<?php

namespace App\Services;

use App\Models\DepositSettlement;
use App\Models\DepositSettlementEvent;
use App\Models\DepositTransaction;
use App\Models\MoveOutRequest;
use App\Models\User;
use App\Notifications\DepositSettlementNotification;
use Illuminate\Support\Facades\DB;

/**
 * Drives the transparent deposit settlement for a single move-out.
 *
 * The service never invents figures: it binds the existing security deposit
 * ledger (receipts, deductions, refunds) to the existing move-out inspections
 * and records an append-only history. Proposed deductions must be reviewed and
 * approved before they can be applied, and a refund is only marked completed
 * through the existing verified refund flow.
 */
class DepositSettlementService
{
    public function __construct(protected DepositService $deposits) {}

    // Get or create the settlement statement for a move-out request.
    public function ensure(MoveOutRequest $request, User $actor): DepositSettlement
    {
        $existing = $request->settlement()->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($request, $actor) {
            $deposit = $request->tenant?->securityDeposit;

            $settlement = DepositSettlement::create([
                'move_out_request_id' => $request->id,
                'security_deposit_id' => $deposit?->id,
                'status' => DepositSettlement::STATUS_DRAFT,
                'held_amount' => (float) ($deposit?->received_amount ?? 0),
            ]);

            $this->refreshFigures($settlement);

            $this->log($settlement, DepositSettlementEvent::EVENT_CREATED, $actor, 'Settlement statement created.');

            return $settlement->fresh();
        });
    }

    // Recompute the held, proposed deduction and proposed refund figures from
    // the deposit ledger. Reflections only — nothing is applied to the ledger.
    public function refreshFigures(DepositSettlement $settlement): DepositSettlement
    {
        $deposit = $settlement->securityDeposit;

        $held = (float) ($deposit?->received_amount ?? 0);

        $pending = $deposit
            ? (float) $deposit->deductions()
                ->whereIn('status', [DepositTransaction::STATUS_PROPOSED, DepositTransaction::STATUS_APPROVED])
                ->sum('amount')
            : 0.0;

        $completedRefunds = (float) ($deposit?->completedRefunds() ?? 0);

        $settlement->update([
            'held_amount' => round($held, 2),
            'proposed_deductions' => round($pending, 2),
            'proposed_refund' => round(max(0.0, $held - $pending - $completedRefunds), 2),
        ]);

        return $settlement->fresh();
    }

    // Send the statement to the tenant for review.
    public function sendToTenant(DepositSettlement $settlement, User $actor): DepositSettlement
    {
        if ($settlement->isSettled()) {
            abort(422, 'A settled statement cannot be re-sent.');
        }

        return DB::transaction(function () use ($settlement, $actor) {
            $this->refreshFigures($settlement);

            $settlement->update([
                'status' => DepositSettlement::STATUS_AWAITING_TENANT,
                'sent_at' => now(),
            ]);

            $this->log($settlement, DepositSettlementEvent::EVENT_SENT_TO_TENANT, $actor, 'Statement sent to the tenant for review.');

            $settlement->moveOutRequest?->tenant?->user?->notify(
                new DepositSettlementNotification($settlement->fresh(), 'sent')
            );

            return $settlement->fresh();
        });
    }

    // Record the tenant's response. Earlier responses are preserved as
    // append-only events; a decision never erases what the tenant said.
    public function recordTenantResponse(DepositSettlement $settlement, User $actor, array $data): DepositSettlement
    {
        if (! $settlement->isVisibleToTenant()) {
            abort(422, 'This statement has not been sent to the tenant yet.');
        }

        if ($settlement->isSettled()) {
            abort(422, 'A settled statement cannot be changed.');
        }

        return DB::transaction(function () use ($settlement, $actor, $data) {
            $deposit = $settlement->securityDeposit;
            $comment = $data['tenant_comment'] ?? null;
            $disputes = array_filter((array) ($data['dispute'] ?? []));

            $disputedCount = 0;

            if ($deposit) {
                foreach ($disputes as $id) {
                    $transaction = $deposit->deductions()->whereKey($id)->first();

                    if ($transaction && $transaction->status !== DepositTransaction::STATUS_REVERSED) {
                        $transaction->update([
                            'disputed' => true,
                            'tenant_comment' => $comment,
                            'responded_at' => now(),
                        ]);

                        $disputedCount++;
                    }
                }
            }

            $updates = [
                'tenant_comment' => $comment,
                'responded_at' => now(),
                'status' => $disputedCount > 0
                    ? DepositSettlement::STATUS_DISPUTED
                    : DepositSettlement::STATUS_TENANT_RESPONDED,
            ];

            if (! empty($data['acknowledge'])) {
                $updates['acknowledged_at'] = now();
            }

            $settlement->update($updates);

            $this->log($settlement, DepositSettlementEvent::EVENT_TENANT_RESPONDED, $actor, $comment);

            if ($disputedCount > 0) {
                $this->log(
                    $settlement,
                    DepositSettlementEvent::EVENT_TENANT_DISPUTED,
                    $actor,
                    'Tenant disputed '.$disputedCount.' proposed deduction(s).',
                    ['count' => $disputedCount]
                );
            }

            if (! empty($data['acknowledge'])) {
                $this->log($settlement, DepositSettlementEvent::EVENT_ACKNOWLEDGED, $actor, 'Tenant acknowledged receipt of the statement.');
            }

            $landlord = $settlement->moveOutRequest?->property?->landlord;
            $landlord?->notify(new DepositSettlementNotification(
                $settlement->fresh(),
                $disputedCount > 0 ? 'disputed' : 'responded'
            ));

            return $settlement->fresh();
        });
    }

    // Record the landlord's decision: approve or reject each proposed deduction
    // and set the approved refund. The decision does not itself pay anyone.
    public function recordDecision(DepositSettlement $settlement, User $actor, array $data): DepositSettlement
    {
        if ($settlement->isSettled()) {
            abort(422, 'A settled statement cannot be changed.');
        }

        return DB::transaction(function () use ($settlement, $actor, $data) {
            $deposit = $settlement->securityDeposit;

            if ($deposit === null) {
                abort(422, 'No security deposit is held for this tenancy.');
            }

            $approveIds = array_filter((array) ($data['approve'] ?? []));
            $rejectIds = array_filter((array) ($data['reject'] ?? []));
            $amounts = (array) ($data['amounts'] ?? []);

            foreach ((array) ($data['decisions'] ?? []) as $id => $decision) {
                if ($decision === 'approve') {
                    $approveIds[] = $id;
                } elseif ($decision === 'reject') {
                    $rejectIds[] = $id;
                }
            }

            foreach ($approveIds as $id) {
                $transaction = $deposit->deductions()->whereKey($id)->first();

                if (! $transaction || $transaction->status !== DepositTransaction::STATUS_PROPOSED) {
                    continue;
                }

                // A deduction that needs explicit authorisation (for example an
                // arrears allocation) can only be approved when confirmed.
                if ($transaction->requires_authorisation && empty($data['confirm_authorisation'])) {
                    abort(422, 'This deduction requires explicit authorisation before it can be approved.');
                }

                if (isset($amounts[$id]) && is_numeric($amounts[$id])) {
                    $transaction->update(['amount' => round((float) $amounts[$id], 2)]);
                }

                $this->deposits->approveDeduction($transaction, $actor);
            }

            foreach ($rejectIds as $id) {
                $transaction = $deposit->deductions()->whereKey($id)->first();

                if ($transaction && $transaction->status === DepositTransaction::STATUS_PROPOSED) {
                    $transaction->update(['status' => DepositTransaction::STATUS_REVERSED]);
                }
            }

            $deposit->refresh();

            $held = (float) $deposit->received_amount;
            $approvedDeductions = $deposit->approvedDeductions();

            if (round($approvedDeductions, 2) > round($held, 2)) {
                abort(422, 'Approved deductions cannot exceed the deposit received.');
            }

            $maxRefund = round($held - $approvedDeductions - $deposit->completedRefunds(), 2);

            $approvedRefund = (isset($data['approved_refund']) && $data['approved_refund'] !== null)
                ? round((float) $data['approved_refund'], 2)
                : max(0.0, $maxRefund);

            if ($approvedRefund < 0 || $approvedRefund > $maxRefund) {
                abort(422, 'The approved refund must be between zero and the refundable balance.');
            }

            $retained = round($held - $approvedRefund, 2);

            // Move the refund through the ledger (proposed -> approved) so the
            // balance is reserved and cannot be promised twice. Nothing is paid
            // until the payout is confirmed at settlement.
            if ($approvedRefund > 0) {
                $deposit->refunds()
                    ->where('move_out_request_id', $settlement->move_out_request_id)
                    ->whereIn('status', [DepositTransaction::STATUS_PROPOSED, DepositTransaction::STATUS_APPROVED])
                    ->get()
                    ->each(fn (DepositTransaction $refund) => $refund->update(['status' => DepositTransaction::STATUS_REVERSED]));

                $refund = $this->deposits->proposeRefund($deposit->fresh(), [
                    'amount' => $approvedRefund,
                    'reason' => $data['decision_reason'] ?? 'Approved deposit refund',
                    'move_out_request_id' => $settlement->move_out_request_id,
                ], $actor);

                $this->deposits->approveRefund($refund, $actor);

                $this->log($settlement, DepositSettlementEvent::EVENT_REFUND_PROPOSED, $actor, null, ['amount' => $approvedRefund]);
                $this->log($settlement, DepositSettlementEvent::EVENT_REFUND_APPROVED, $actor, 'Refund approved, pending payout.');
            }

            $settlement->update([
                'status' => DepositSettlement::STATUS_APPROVED,
                'approved_refund' => $approvedRefund,
                'retained_amount' => $retained,
                'landlord_findings' => $data['landlord_findings'] ?? null,
                'decision_reason' => $data['decision_reason'] ?? null,
                'decided_at' => now(),
                'decided_by' => $actor->id,
            ]);

            $this->refreshFigures($settlement);

            $this->log($settlement, DepositSettlementEvent::EVENT_DECISION_RECORDED, $actor, $data['landlord_findings'] ?? null, [
                'approved_refund' => $approvedRefund,
                'retained' => $retained,
            ]);

            $settlement->moveOutRequest?->tenant?->user?->notify(
                new DepositSettlementNotification($settlement->fresh(), 'decision')
            );

            return $settlement->fresh();
        });
    }

    // Complete the settlement by confirming the approved refund payout through
    // the existing verified refund flow, then marking the statement settled.
    public function settle(DepositSettlement $settlement, User $actor, array $data): DepositSettlement
    {
        if (! $settlement->isApproved()) {
            abort(422, 'Only an approved statement can be settled.');
        }

        return DB::transaction(function () use ($settlement, $actor, $data) {
            $deposit = $settlement->securityDeposit;
            $approvedRefund = (float) ($settlement->approved_refund ?? 0);

            if ($approvedRefund > 0) {
                if ($deposit === null) {
                    abort(422, 'No security deposit is held for this tenancy.');
                }

                $refund = $deposit->refunds()
                    ->where('move_out_request_id', $settlement->move_out_request_id)
                    ->where('status', DepositTransaction::STATUS_APPROVED)
                    ->latest('id')
                    ->first();

                if ($refund === null) {
                    abort(422, 'The approved refund could not be found.');
                }

                $this->deposits->completeRefund($refund, [
                    'method' => $data['method'] ?? null,
                    'reference' => $data['reference'] ?? null,
                ], $actor);
            }

            $deposit?->refresh();

            $refunded = (float) ($deposit?->completedRefunds() ?? 0);
            $held = (float) $settlement->held_amount;

            $settlement->update([
                'status' => DepositSettlement::STATUS_SETTLED,
                'refunded_amount' => round($refunded, 2),
                'retained_amount' => round($held - $refunded, 2),
                'settled_at' => now(),
                'settled_by' => $actor->id,
            ]);

            $this->refreshFigures($settlement);

            $this->log($settlement, DepositSettlementEvent::EVENT_SETTLED, $actor, 'Settlement completed.', ['refunded' => $refunded]);

            $settlement->moveOutRequest?->tenant?->user?->notify(
                new DepositSettlementNotification($settlement->fresh(), 'settled')
            );

            return $settlement->fresh();
        });
    }

    // Append an entry to the settlement history. Existing entries are never
    // edited or deleted.
    protected function log(DepositSettlement $settlement, string $event, ?User $actor, ?string $message = null, array $data = []): DepositSettlementEvent
    {
        return $settlement->events()->create([
            'user_id' => $actor?->id,
            'role' => $actor?->role,
            'event' => $event,
            'message' => $message,
            'data' => $data ?: null,
        ]);
    }
}

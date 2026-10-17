<?php

namespace App\Services;

use App\Models\MoveOutInspection;
use App\Models\MoveOutRequest;
use App\Models\RentAudit;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InspectionAssignedNotification;
use App\Notifications\InspectionCompletedNotification;
use App\Notifications\MoveOutRequestSubmittedNotification;
use App\Notifications\MoveOutStatusChangedNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoveOutService
{
    public function __construct(
        protected SmsService $sms,
        protected AuditService $audit,
        protected TurnoverService $turnovers,
    ) {}

    // Earliest date a tenant may move out given the submission date and notice period
    // (calendar months, not a fixed 30-day month).
    public function earliestPermittedDate(CarbonInterface $submittedAt, float $months): Carbon
    {
        $date = $submittedAt->copy();

        $wholeMonths = (int) floor($months);
        if ($wholeMonths > 0) {
            $date = $date->addMonthsNoOverflow($wholeMonths);
        }

        $fraction = $months - $wholeMonths;
        if ($fraction > 0) {
            $date = $date->addDays((int) round($fraction * 30.44));
        }

        return Carbon::parse($date)->startOfDay();
    }

    public function noticePeriodMonths(): float
    {
        return (float) config('tenancy.notice_period_months', 1);
    }

    public function formatPeriod(float $months): string
    {
        if ($months == 1.0) {
            return 'one month';
        }

        return rtrim(rtrim(number_format($months, 2), '0'), '.');
    }

    // Create a tenant's notice-to-vacate request.
    public function createRequest(Tenant $tenant, array $data, User $submittedBy): MoveOutRequest
    {
        if ($tenant->activeMoveOutRequest()->exists()) {
            throw ValidationException::withMessages([
                'proposed_move_out_date' => 'You already have an open Notice to Vacate request.',
            ]);
        }

        $months = $this->noticePeriodMonths();
        $submittedAt = now();
        $earliest = $this->earliestPermittedDate($submittedAt, $months);
        $proposed = Carbon::parse($data['proposed_move_out_date'])->startOfDay();

        if ($proposed->lt($earliest)) {
            throw ValidationException::withMessages([
                'proposed_move_out_date' => 'Your proposed move-out date must be on or after '
                    .$earliest->format('d M Y').' to satisfy the '
                    .$this->formatPeriod($months).' notice period.',
            ]);
        }

        return DB::transaction(function () use ($tenant, $data, $submittedBy, $months, $submittedAt, $earliest, $proposed) {
            $request = MoveOutRequest::create([
                'tenant_id' => $tenant->id,
                'property_id' => $tenant->property_id,
                'unit_id' => $tenant->unit_id,
                'status' => MoveOutRequest::STATUS_SUBMITTED,
                'submitted_at' => $submittedAt,
                'proposed_move_out_date' => $proposed->toDateString(),
                'notice_period_months' => $months,
                'earliest_permitted_date' => $earliest->toDateString(),
                'reason' => $data['reason'] ?? null,
                'acknowledged_at' => $data['acknowledged_at'] ?? now(),
            ]);

            RentAudit::record('move_out_requested', $request, [
                'proposed_move_out_date' => $request->proposed_move_out_date->toDateString(),
                'notice_period_months' => $months,
            ], [], $submittedBy->id);

            // A move-out notice opens the coordinated turnover workspace. The
            // unit is never vacated or the tenant deactivated at this point.
            $this->turnovers->open($request, $submittedBy);

            $landlord = $request->property?->landlord;
            $landlord?->notify(new MoveOutRequestSubmittedNotification($request));

            $this->sms->sendTemplate('move_out_update', (string) $tenant->phone, [
                'name' => $tenant->full_name,
                'status' => MoveOutRequest::statusLabel(MoveOutRequest::STATUS_SUBMITTED),
            ], ['related' => $request]);

            return $request;
        });
    }

    // Move a request through the workflow, enforcing valid transitions.
    public function transition(MoveOutRequest $request, string $status, User $actor, array $meta = []): MoveOutRequest
    {
        if (! $request->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status transition from "'
                    .MoveOutRequest::statusLabel($request->status).'" to "'
                    .MoveOutRequest::statusLabel($status).'".',
            ]);
        }

        return DB::transaction(function () use ($request, $status, $actor, $meta) {
            $old = $request->status;
            $updates = ['status' => $status];

            if (in_array($status, [
                MoveOutRequest::STATUS_UNDER_REVIEW,
                MoveOutRequest::STATUS_AWAITING_TENANT_INFO,
                MoveOutRequest::STATUS_APPROVED,
                MoveOutRequest::STATUS_REJECTED,
            ], true)) {
                $updates['reviewed_by'] = $actor->id;
                $updates['reviewed_at'] = now();

                if (array_key_exists('decision_reason', $meta)) {
                    $updates['decision_reason'] = $meta['decision_reason'];
                }
                if (array_key_exists('clarification_message', $meta)) {
                    $updates['clarification_message'] = $meta['clarification_message'];
                }
            }

            if ($status === MoveOutRequest::STATUS_COMPLETED) {
                $updates['clearance_decision'] = $meta['clearance_decision'] ?? 'cleared';
                $updates['cleared_by'] = $actor->id;
                $updates['cleared_at'] = now();
                $updates['actual_move_out_date'] = $meta['actual_move_out_date']
                    ?? $request->proposed_move_out_date?->toDateString();
                if (array_key_exists('notes', $meta)) {
                    $updates['notes'] = $meta['notes'];
                }
            }

            $request->update($updates);

            if ($status === MoveOutRequest::STATUS_COMPLETED) {
                $this->closeTenancy($request->fresh());
            }

            RentAudit::record('move_out_status_changed', $request, [
                'from' => $old,
                'to' => $status,
            ], [], $actor->id);

            // Keep the turnover workspace consistent with the move-out outcome
            // (including cancellations and rejections).
            $this->turnovers->syncForMoveOut($request->fresh(), $actor);

            if ($request->tenant?->user) {
                $request->tenant->user->notify(new MoveOutStatusChangedNotification($request->fresh(), $old));
            }

            $this->sms->sendTemplate('move_out_update', (string) $request->tenant?->phone, [
                'name' => $request->tenant?->full_name,
                'status' => MoveOutRequest::statusLabel($status),
            ], ['related' => $request->fresh()]);

            return $request->fresh();
        });
    }

    // Assign a move-out inspection to a caretaker and seed the checklist.
    public function assignInspection(MoveOutRequest $request, User $caretaker, ?string $scheduledAt, User $actor): MoveOutInspection
    {
        if (! in_array($request->status, [
            MoveOutRequest::STATUS_APPROVED,
            MoveOutRequest::STATUS_INSPECTION_SCHEDULED,
        ], true)) {
            throw ValidationException::withMessages([
                'caretaker_id' => 'An inspection can only be assigned once the request is approved.',
            ]);
        }

        return DB::transaction(function () use ($request, $caretaker, $scheduledAt, $actor) {
            $inspection = $request->inspections()->create([
                'caretaker_id' => $caretaker->id,
                'status' => MoveOutInspection::STATUS_ASSIGNED,
                'scheduled_at' => $scheduledAt ? Carbon::parse($scheduledAt) : null,
            ]);

            foreach (MoveOutInspection::checklist() as $category => $group) {
                foreach ($group['items'] as $label) {
                    $inspection->items()->create([
                        'category' => $category,
                        'label' => $label,
                    ]);
                }
            }

            if ($request->status === MoveOutRequest::STATUS_APPROVED) {
                $request->update(['status' => MoveOutRequest::STATUS_INSPECTION_SCHEDULED]);
            }

            $caretaker->notify(new InspectionAssignedNotification($inspection));

            RentAudit::record('move_out_inspection_assigned', $request, [
                'caretaker_id' => $caretaker->id,
                'scheduled_at' => optional($inspection->scheduled_at)->toDateTimeString(),
            ], [], $actor->id);

            return $inspection->fresh(['items']);
        });
    }

    // Submit a caretaker's inspection findings and recommendations.
    public function submitInspection(MoveOutInspection $inspection, array $data, User $submittedBy): MoveOutInspection
    {
        if ($inspection->isSubmitted()) {
            throw ValidationException::withMessages([
                'status' => 'This inspection has already been submitted.',
            ]);
        }

        return DB::transaction(function () use ($inspection, $data, $submittedBy) {
            $inspection->update([
                'status' => MoveOutInspection::STATUS_SUBMITTED,
                'completed_at' => now(),
                'keys_returned' => $data['keys_returned'] ?? null,
                'keys_notes' => $data['keys_notes'] ?? null,
                'electricity_meter' => $data['electricity_meter'] ?? null,
                'water_meter' => $data['water_meter'] ?? null,
                'cleanliness' => $data['cleanliness'] ?? null,
                'general_notes' => $data['general_notes'] ?? null,
                'photos' => array_values(array_merge(
                    $inspection->photos ?? [],
                    $data['photos'] ?? [],
                )),
                'recommendation' => $data['recommendation'] ?? null,
                'submitted_by' => $submittedBy->id,
            ]);

            foreach (($data['items'] ?? []) as $itemId => $values) {
                $item = $inspection->items()->whereKey($itemId)->first();
                if ($item) {
                    $item->update([
                        'condition' => $values['condition'] ?? null,
                        'nature' => $values['nature'] ?? null,
                        'comment' => $values['comment'] ?? null,
                    ]);
                }
            }

            $request = $inspection->moveOutRequest;
            if ($request->canTransitionTo(MoveOutRequest::STATUS_INSPECTION_COMPLETED)) {
                $request->update(['status' => MoveOutRequest::STATUS_INSPECTION_COMPLETED]);
            }

            $this->turnovers->syncForMoveOut($request->fresh(), $submittedBy);

            $landlord = $request->property?->landlord;
            $landlord?->notify(new InspectionCompletedNotification($inspection->fresh(['items'])));

            RentAudit::record('move_out_inspection_submitted', $request, [
                'inspection_id' => $inspection->id,
                'recommendation' => $inspection->recommendation,
            ], [], $submittedBy->id);

            return $inspection->fresh(['items']);
        });
    }

    // Complete final clearance and close the tenancy.
    public function clear(MoveOutRequest $request, User $actor, array $data): MoveOutRequest
    {
        if (! in_array($request->status, [
            MoveOutRequest::STATUS_APPROVED,
            MoveOutRequest::STATUS_INSPECTION_SCHEDULED,
            MoveOutRequest::STATUS_INSPECTION_COMPLETED,
            MoveOutRequest::STATUS_AWAITING_CLEARANCE,
        ], true)) {
            throw ValidationException::withMessages([
                'clearance_decision' => 'This request is not ready for clearance.',
            ]);
        }

        // When a deposit settlement has been started for this move-out, it must
        // be settled before the tenancy is closed. Existing move-outs without a
        // settlement keep their original clearance flow.
        $settlement = $request->settlement()->first();

        if ($settlement && ! $settlement->isSettled()) {
            throw ValidationException::withMessages([
                'clearance_decision' => 'The deposit settlement must be settled before the tenancy can be cleared.',
            ]);
        }

        if ($request->status !== MoveOutRequest::STATUS_AWAITING_CLEARANCE
            && $request->canTransitionTo(MoveOutRequest::STATUS_AWAITING_CLEARANCE)) {
            $request->update(['status' => MoveOutRequest::STATUS_AWAITING_CLEARANCE]);
            $request->refresh();
        }

        return $this->transition($request, MoveOutRequest::STATUS_COMPLETED, $actor, [
            'clearance_decision' => $data['clearance_decision'] ?? 'cleared',
            'actual_move_out_date' => $data['actual_move_out_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    // Deactivate the tenancy and free the unit once clearance is complete.
    protected function closeTenancy(MoveOutRequest $request): void
    {
        $request->tenant?->update([
            'is_active' => false,
            'move_out_date' => $request->actual_move_out_date?->toDateString() ?? now()->toDateString(),
        ]);

        $request->unit?->update(['status' => 'vacant']);

        if ($request->unit) {
            $this->audit->record(
                'unit_status_changed',
                'Unit '.$request->unit->unit_number.' status changed to Vacant on move-out clearance.',
                [],
                ['status' => 'vacant'],
                $request->unit
            );
        }

        if ($request->tenant?->user_id) {
            $request->tenant->user->update(['is_active' => false]);
        }
    }
}

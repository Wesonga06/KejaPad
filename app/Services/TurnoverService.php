<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestEvent;
use App\Models\MoveOutRequest;
use App\Models\Tenant;
use App\Models\Turnover;
use App\Models\TurnoverEvent;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\TurnoverTaskNotification;
use Illuminate\Support\Facades\DB;

/**
 * Drives the coordinated turnover of a unit from a move-out notice through
 * inspection, repairs, clearance, readiness and the next tenant's move-in.
 *
 * The service never duplicates the underlying records: it references the
 * existing move-out request, inspections, maintenance tasks, deposit settlement
 * and tenancy records, and only stores the turnover-specific decisions
 * (handover, readiness confirmation and the incoming tenancy link).
 */
class TurnoverService
{
    public function __construct(protected AuditService $audit) {}

    // Open the turnover workspace for a move-out request (idempotent).
    public function open(MoveOutRequest $request, User $actor): Turnover
    {
        $existing = $request->turnover()->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($request, $actor) {
            $turnover = Turnover::create([
                'move_out_request_id' => $request->id,
                'property_id' => $request->property_id,
                'unit_id' => $request->unit_id,
                'outgoing_tenant_id' => $request->tenant_id,
                'status' => Turnover::STATUS_MOVE_OUT_PLANNED,
                'last_activity_at' => now(),
            ]);

            $this->log($turnover, TurnoverEvent::EVENT_OPENED, $actor, 'Turnover workspace opened.');
            $this->audit->record(
                'turnover_opened',
                'Turnover opened for unit '.($turnover->unit?->unit_number ?? '—').'.',
                [],
                ['status' => $turnover->status],
                $turnover,
                $actor
            );

            return $this->sync($turnover->fresh(), $actor);
        });
    }

    // Recompute a turnover's status from the underlying records.
    public function sync(Turnover $turnover, ?User $actor = null): Turnover
    {
        $turnover->refresh();
        $turnover->load(['moveOutRequest.tenant', 'tasks']);

        $old = $turnover->status;
        $new = $this->deriveStatus($turnover);

        $updates = ['status' => $new, 'last_activity_at' => now()];

        if ($new === Turnover::STATUS_CANCELLED && $turnover->cancelled_at === null) {
            $updates['cancelled_at'] = now();
        }

        if ($new === Turnover::STATUS_OCCUPIED && $turnover->completed_at === null) {
            $updates['completed_at'] = now();
            $updates['new_tenancy_started_at'] = $turnover->new_tenancy_started_at ?? now();
        }

        $turnover->update($updates);
        $turnover = $turnover->fresh();

        if ($old !== $turnover->status) {
            $this->log($turnover, TurnoverEvent::EVENT_STATUS_CHANGED, $actor, null, [
                'from' => $old,
                'to' => $turnover->status,
            ]);

            $this->audit->record(
                'turnover_status_changed',
                'Turnover for unit '.($turnover->unit?->unit_number ?? '—').' is now '.$turnover->statusLabel().'.',
                ['status' => $old],
                ['status' => $turnover->status],
                $turnover,
                $actor
            );
        }

        return $turnover;
    }

    // Recompute the turnover linked to a move-out request.
    public function syncForMoveOut(MoveOutRequest $request, ?User $actor = null): void
    {
        $turnover = $request->turnover()->first();

        if ($turnover) {
            $this->sync($turnover, $actor);
        }
    }

    // Recompute every open turnover for a unit.
    public function syncForUnit(Unit $unit, ?User $actor = null): void
    {
        Turnover::where('unit_id', $unit->id)
            ->whereNotIn('status', [Turnover::STATUS_CANCELLED, Turnover::STATUS_OCCUPIED])
            ->get()
            ->each(fn (Turnover $turnover) => $this->sync($turnover, $actor));
    }

    public function syncForMaintenance(MaintenanceRequest $task, ?User $actor = null): void
    {
        if ($task->turnover_id) {
            $turnover = $task->turnover;

            if ($turnover) {
                $this->sync($turnover, $actor);
            }
        }
    }

    // Derive the operational status from existing records only.
    protected function deriveStatus(Turnover $turnover): string
    {
        $moveOut = $turnover->moveOutRequest;

        if ($turnover->cancelled_at !== null) {
            return Turnover::STATUS_CANCELLED;
        }

        if ($moveOut && in_array($moveOut->status, [
            MoveOutRequest::STATUS_REJECTED,
            MoveOutRequest::STATUS_CANCELLED,
        ], true)) {
            return Turnover::STATUS_CANCELLED;
        }

        // Once the incoming tenancy is active and the unit is occupied again the
        // turnover is complete.
        if ($turnover->next_tenant_id) {
            $next = $turnover->nextTenant;

            if ($next && $next->is_active && $turnover->unit?->status === 'occupied') {
                return Turnover::STATUS_OCCUPIED;
            }
        }

        $tasks = $turnover->tasks;

        if ($tasks->whereIn('status', ['pending', 'assigned', 'in_progress'])->isNotEmpty()) {
            return Turnover::STATUS_REPAIRS_REQUIRED;
        }

        if ($tasks->whereIn('status', ['resolved', 'closed'])->whereNull('verified_at')->isNotEmpty()) {
            return Turnover::STATUS_AWAITING_VERIFICATION;
        }

        if ($turnover->readiness_confirmed_at !== null) {
            return $turnover->next_tenant_id
                ? Turnover::STATUS_NEW_TENANCY_PENDING
                : Turnover::STATUS_READY_FOR_LETTING;
        }

        if ($moveOut && $moveOut->status === MoveOutRequest::STATUS_COMPLETED) {
            // Cleared but awaiting the landlord's readiness confirmation.
            return Turnover::STATUS_AWAITING_VERIFICATION;
        }

        $inspection = $moveOut?->inspection()->first();

        if ($moveOut && in_array($moveOut->status, [
            MoveOutRequest::STATUS_APPROVED,
            MoveOutRequest::STATUS_INSPECTION_SCHEDULED,
            MoveOutRequest::STATUS_INSPECTION_COMPLETED,
            MoveOutRequest::STATUS_AWAITING_CLEARANCE,
        ], true)) {
            return $inspection && $inspection->isSubmitted()
                ? Turnover::STATUS_AWAITING_VERIFICATION
                : Turnover::STATUS_INSPECTION_PENDING;
        }

        return Turnover::STATUS_MOVE_OUT_PLANNED;
    }

    // ---- Tasks (real maintenance requests) --------------------------------

    public function addTask(Turnover $turnover, array $data, User $actor): MaintenanceRequest
    {
        $this->assertOpen($turnover);

        return DB::transaction(function () use ($turnover, $data, $actor) {
            $task = MaintenanceRequest::create([
                'tenant_id' => $turnover->outgoing_tenant_id,
                'property_id' => $turnover->property_id,
                'unit_id' => $turnover->unit_id,
                'turnover_id' => $turnover->id,
                'assigned_to' => $data['assigned_to'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? $data['title'],
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'] ?? 'medium',
                'due_date' => $data['due_date'] ?? null,
                'status' => ! empty($data['assigned_to']) ? 'assigned' : 'pending',
            ]);

            $this->log($turnover, TurnoverEvent::EVENT_TASK_ADDED, $actor, $task->title, ['task_id' => $task->id]);
            $this->audit->record(
                'turnover_task_added',
                'Turnover task "'.$task->title.'" added.',
                [],
                ['task_id' => $task->id, 'assigned_to' => $task->assigned_to],
                $turnover,
                $actor
            );

            if ($task->assigned_to && $task->assignedTo) {
                $task->assignedTo->notify(new TurnoverTaskNotification($task->fresh(), 'assigned'));
                app(MaintenanceAccountabilityService::class)->recordEvent(
                    $task,
                    MaintenanceRequestEvent::EVENT_ASSIGNED,
                    $actor,
                    'Assigned as part of the turnover.',
                    ['to' => $task->assigned_to]
                );
            }

            $this->sync($turnover, $actor);

            return $task->fresh();
        });
    }

    public function assignTask(Turnover $turnover, MaintenanceRequest $task, ?User $caretaker, User $actor): MaintenanceRequest
    {
        $this->assertTaskBelongs($turnover, $task);
        $this->assertOpen($turnover);

        $previous = $task->assigned_to;

        $task->update([
            'assigned_to' => $caretaker?->id,
            'status' => $caretaker ? 'assigned' : 'pending',
        ]);

        $this->log($turnover, TurnoverEvent::EVENT_TASK_ASSIGNED, $actor, $task->title, [
            'task_id' => $task->id,
            'from' => $previous,
            'to' => $caretaker?->id,
        ]);
        $this->audit->record(
            'turnover_task_assigned',
            'Turnover task "'.$task->title.'" assigned to '.($caretaker?->name ?? 'nobody').'.',
            ['assigned_to' => $previous],
            ['assigned_to' => $caretaker?->id],
            $turnover,
            $actor
        );

        if ($caretaker) {
            $caretaker->notify(new TurnoverTaskNotification($task->fresh(), 'assigned'));
        }

        app(MaintenanceAccountabilityService::class)->recordEvent(
            $task,
            match (true) {
                $caretaker === null => MaintenanceRequestEvent::EVENT_UNASSIGNED,
                $previous !== null && $previous !== $caretaker->id => MaintenanceRequestEvent::EVENT_REASSIGNED,
                default => MaintenanceRequestEvent::EVENT_ASSIGNED,
            },
            $actor,
            $caretaker ? 'Assigned to '.$caretaker->name.' (turnover).' : 'Returned to the unassigned pool (turnover).',
            ['from' => $previous, 'to' => $caretaker?->id]
        );

        $this->sync($turnover, $actor);

        return $task->fresh();
    }

    public function verifyTask(Turnover $turnover, MaintenanceRequest $task, User $actor, array $data): MaintenanceRequest
    {
        $this->assertTaskBelongs($turnover, $task);

        if (! in_array($task->status, ['resolved', 'closed'], true)) {
            abort(422, 'Only a completed repair task can be verified.');
        }

        $task->update([
            'status' => 'closed',
            'verified_at' => now(),
            'verified_by' => $actor->id,
            'verification_notes' => $data['verification_notes'] ?? null,
        ]);

        $this->log($turnover, TurnoverEvent::EVENT_TASK_VERIFIED, $actor, $task->title, ['task_id' => $task->id]);
        $this->audit->record(
            'turnover_task_verified',
            'Turnover task "'.$task->title.'" verified complete.',
            [],
            ['task_id' => $task->id],
            $turnover,
            $actor
        );

        if ($task->assignedTo) {
            $task->assignedTo->notify(new TurnoverTaskNotification($task->fresh(), 'verified'));
        }

        app(MaintenanceAccountabilityService::class)->recordEvent(
            $task,
            MaintenanceRequestEvent::EVENT_VERIFIED,
            $actor,
            $data['verification_notes'] ?? 'Turnover repair verified complete.',
            ['notes' => $data['verification_notes'] ?? null]
        );

        $this->sync($turnover, $actor);

        return $task->fresh();
    }

    // ---- Handover, readiness and onboarding -------------------------------

    public function recordHandover(Turnover $turnover, array $data, User $actor): Turnover
    {
        $this->assertOpen($turnover);

        $turnover->update([
            'handover_notes' => $data['handover_notes'] ?? null,
            'handover_recorded_at' => now(),
            'handover_recorded_by' => $actor->id,
        ]);

        $this->log($turnover, TurnoverEvent::EVENT_HANDOVER_RECORDED, $actor, 'Keys and handover information recorded.');
        $this->audit->record(
            'turnover_handover_recorded',
            'Handover recorded for unit '.($turnover->unit?->unit_number ?? '—').'.',
            [],
            ['keys_returned' => $data['keys_returned'] ?? null],
            $turnover,
            $actor
        );

        return $this->sync($turnover->fresh(), $actor);
    }

    public function markReady(Turnover $turnover, User $actor, array $data): Turnover
    {
        $turnover->load(['moveOutRequest', 'unit', 'tasks']);

        if ($turnover->isCancelled()) {
            abort(422, 'A cancelled turnover cannot be marked ready.');
        }

        $moveOut = $turnover->moveOutRequest;

        if (! $moveOut || $moveOut->status !== MoveOutRequest::STATUS_COMPLETED) {
            abort(422, 'The outgoing tenancy must be cleared before the unit can be marked ready.');
        }

        $inspection = $moveOut->inspection()->first();

        if (! $inspection || ! $inspection->isSubmitted()) {
            abort(422, 'The move-out inspection must be completed before the unit can be marked ready.');
        }

        if ($turnover->openTasks()->exists()) {
            abort(422, 'All repair tasks must be completed before the unit can be marked ready.');
        }

        if ($turnover->tasks()->whereIn('status', ['resolved', 'closed'])->whereNull('verified_at')->exists()) {
            abort(422, 'Resolved repair tasks must be verified before the unit can be marked ready.');
        }

        DB::transaction(function () use ($turnover, $actor, $data) {
            $turnover->update([
                'readiness_confirmed_at' => now(),
                'readiness_confirmed_by' => $actor->id,
                'readiness_notes' => $data['readiness_notes'] ?? null,
            ]);

            $this->log($turnover, TurnoverEvent::EVENT_READY_CONFIRMED, $actor, $data['readiness_notes'] ?? 'Unit confirmed ready for letting.');
            $this->audit->record(
                'turnover_ready_confirmed',
                'Unit '.($turnover->unit?->unit_number ?? '—').' confirmed ready for letting.',
                [],
                ['readiness_confirmed_at' => now()->toIso8601String()],
                $turnover,
                $actor
            );

            $this->sync($turnover, $actor);
        });

        return $turnover->fresh();
    }

    // Validate that a new tenancy may begin for this unit, and record the intent.
    public function beginOnboarding(Turnover $turnover, User $actor): Turnover
    {
        $turnover->load(['unit', 'moveOutRequest']);

        if (! $turnover->isReadyForLetting()) {
            abort(422, 'The unit must be confirmed ready before a new tenant can be onboarded.');
        }

        if ($turnover->unit?->status !== 'vacant') {
            abort(422, 'The unit must be vacant before a new tenancy can begin.');
        }

        if (Tenant::where('unit_id', $turnover->unit_id)->where('is_active', true)->exists()) {
            abort(422, 'A new tenancy cannot overlap an existing active tenancy for this unit.');
        }

        DB::transaction(function () use ($turnover, $actor) {
            $this->log($turnover, TurnoverEvent::EVENT_ONBOARDING_STARTED, $actor, 'Onboarding started for the incoming tenant.');
            $this->audit->record(
                'turnover_onboarding_started',
                'Onboarding started for unit '.($turnover->unit?->unit_number ?? '—').'.',
                [],
                ['turnover_id' => $turnover->id],
                $turnover,
                $actor
            );
        });

        return $turnover->fresh();
    }

    // Link a newly created tenancy to the ready turnover for its unit.
    public function linkIncomingTenant(Unit $unit, Tenant $tenant, User $actor): ?Turnover
    {
        $turnover = Turnover::where('unit_id', $unit->id)
            ->whereNotNull('readiness_confirmed_at')
            ->whereNotIn('status', [Turnover::STATUS_CANCELLED, Turnover::STATUS_OCCUPIED])
            ->latest('id')
            ->first();

        if (! $turnover) {
            return null;
        }

        DB::transaction(function () use ($turnover, $tenant, $actor) {
            $turnover->update(['next_tenant_id' => $tenant->id]);

            $this->log($turnover, TurnoverEvent::EVENT_INCOMING_TENANT_LINKED, $actor, 'Incoming tenancy linked.', ['tenant_id' => $tenant->id]);
            $this->audit->record(
                'turnover_incoming_tenant_linked',
                'Incoming tenancy linked to unit '.($turnover->unit?->unit_number ?? '—').'.',
                [],
                ['tenant_id' => $tenant->id],
                $turnover,
                $actor
            );

            if ($turnover->unit && $turnover->unit->status === 'occupied') {
                $this->completeIncomingTenancy($turnover->unit, $actor);
            } else {
                $this->sync($turnover, $actor);
            }
        });

        return $turnover->fresh();
    }

    // Complete a turnover once the incoming tenancy is actually occupied.
    public function completeIncomingTenancy(Unit $unit, ?User $actor = null): void
    {
        $turnover = Turnover::where('unit_id', $unit->id)
            ->whereNotNull('next_tenant_id')
            ->whereNotIn('status', [Turnover::STATUS_CANCELLED, Turnover::STATUS_OCCUPIED])
            ->latest('id')
            ->first();

        if (! $turnover) {
            return;
        }

        $next = $turnover->nextTenant;

        if (! $next || ! $next->is_active || $unit->status !== 'occupied') {
            return;
        }

        DB::transaction(function () use ($turnover, $actor) {
            $turnover->update([
                'status' => Turnover::STATUS_OCCUPIED,
                'new_tenancy_started_at' => $turnover->new_tenancy_started_at ?? now(),
                'completed_at' => now(),
                'last_activity_at' => now(),
            ]);

            $this->log($turnover, TurnoverEvent::EVENT_COMPLETED, $actor, 'New tenancy moved in; turnover complete.');
            $this->audit->record(
                'turnover_completed',
                'Turnover completed for unit '.($turnover->unit?->unit_number ?? '—').'.',
                ['status' => Turnover::STATUS_NEW_TENANCY_PENDING],
                ['status' => Turnover::STATUS_OCCUPIED],
                $turnover,
                $actor
            );
        });
    }

    // ---- Derived views -----------------------------------------------------

    /**
     * The turnover checklist, derived from the underlying records.
     *
     * @return array<int, array{key: string, label: string, mandatory: bool, complete: bool}>
     */
    public function checklist(Turnover $turnover): array
    {
        $turnover->load(['moveOutRequest.inspection', 'nextTenant']);
        $moveOut = $turnover->moveOutRequest;
        $inspection = $moveOut?->inspection()->first();
        $deposit = $moveOut?->tenant?->securityDeposit;
        $settlement = $moveOut?->settlement()->first();

        $tasks = $turnover->tasks;
        $completed = [
            'notice_received' => $moveOut !== null,
            'reviewed' => $moveOut?->reviewed_at !== null,
            'date_confirmed' => $moveOut !== null && $moveOut->reviewed_at !== null
                && in_array($moveOut->status, [
                    MoveOutRequest::STATUS_APPROVED,
                    MoveOutRequest::STATUS_INSPECTION_SCHEDULED,
                    MoveOutRequest::STATUS_INSPECTION_COMPLETED,
                    MoveOutRequest::STATUS_AWAITING_CLEARANCE,
                    MoveOutRequest::STATUS_COMPLETED,
                ], true),
            'inspection_scheduled' => $moveOut?->inspections()->exists() ?? false,
            'inspection_completed' => $inspection !== null && $inspection->isSubmitted(),
            'repairs_assessed' => $tasks->where('status', 'pending')->isEmpty(),
            'repairs_verified' => $tasks->whereIn('status', ['resolved', 'closed'])->whereNull('verified_at')->isEmpty(),
            'handover_recorded' => $turnover->handover_recorded_at !== null
                || (bool) ($inspection?->keys_returned),
            'settlement_reviewed' => $deposit === null
                || ($settlement && ($settlement->isApproved() || $settlement->isSettled())),
            'clearance_completed' => $moveOut?->status === MoveOutRequest::STATUS_COMPLETED,
            'unit_ready' => $turnover->readiness_confirmed_at !== null,
            'new_move_in' => $turnover->nextTenant?->is_active
                && (bool) $turnover->incomingMoveInInspection()?->isSubmitted(),
        ];

        $stages = [];

        foreach (config('turnover.stages', []) as $key => $meta) {
            $stages[] = [
                'key' => $key,
                'label' => $meta['label'],
                'mandatory' => (bool) ($meta['mandatory'] ?? false),
                'complete' => (bool) ($completed[$key] ?? false),
            ];
        }

        return $stages;
    }

    /**
     * The next expected action and the role responsible for it.
     *
     * @return array{label: string, role: ?string}
     */
    public function nextAction(Turnover $turnover): array
    {
        $moveOut = $turnover->moveOutRequest;

        if ($turnover->isCancelled()) {
            return ['label' => 'No action — the move-out was cancelled or rejected.', 'role' => null];
        }

        if ($turnover->status === Turnover::STATUS_OCCUPIED) {
            return ['label' => 'Turnover complete.', 'role' => null];
        }

        if ($turnover->openTasks()->exists()) {
            return ['label' => 'Complete the assigned repair tasks.', 'role' => 'Caretaker'];
        }

        if ($turnover->tasks()->whereIn('status', ['resolved', 'closed'])->whereNull('verified_at')->exists()) {
            return ['label' => 'Verify completed repair work.', 'role' => 'Landlord'];
        }

        if ($moveOut && $moveOut->status === MoveOutRequest::STATUS_SUBMITTED) {
            return ['label' => 'Review the Notice to Vacate.', 'role' => 'Landlord'];
        }

        if ($moveOut && $moveOut->status === MoveOutRequest::STATUS_AWAITING_TENANT_INFO) {
            return ['label' => 'Awaiting further information from the tenant.', 'role' => 'Tenant'];
        }

        if ($moveOut && in_array($moveOut->status, [MoveOutRequest::STATUS_UNDER_REVIEW], true)) {
            return ['label' => 'Approve the move-out request.', 'role' => 'Landlord'];
        }

        if ($moveOut && in_array($moveOut->status, [
            MoveOutRequest::STATUS_APPROVED,
            MoveOutRequest::STATUS_INSPECTION_SCHEDULED,
        ], true)) {
            $inspection = $moveOut->inspection()->first();

            if (! $inspection) {
                return ['label' => 'Assign a move-out inspection to a caretaker.', 'role' => 'Landlord'];
            }

            if (! $inspection->isSubmitted()) {
                return ['label' => 'Complete and submit the move-out inspection.', 'role' => 'Caretaker'];
            }
        }

        if ($moveOut && $moveOut->status !== MoveOutRequest::STATUS_COMPLETED) {
            return ['label' => 'Complete clearance of the outgoing tenancy.', 'role' => 'Landlord'];
        }

        if ($turnover->readiness_confirmed_at === null) {
            return ['label' => 'Confirm the unit is ready for letting.', 'role' => 'Landlord'];
        }

        if (! $turnover->next_tenant_id) {
            return ['label' => 'Begin onboarding a new tenant.', 'role' => 'Landlord'];
        }

        return ['label' => 'Confirm the incoming tenancy move-in.', 'role' => 'Landlord'];
    }

    protected function assertOpen(Turnover $turnover): void
    {
        if ($turnover->isClosed()) {
            abort(422, 'This turnover is closed and can no longer be changed.');
        }
    }

    protected function assertTaskBelongs(Turnover $turnover, MaintenanceRequest $task): void
    {
        if ($task->turnover_id !== $turnover->id) {
            abort(404, 'This task does not belong to the turnover.');
        }
    }

    // Append an entry to the turnover history. Entries are never edited.
    protected function log(Turnover $turnover, string $event, ?User $actor, ?string $message = null, array $data = []): TurnoverEvent
    {
        return $turnover->events()->create([
            'user_id' => $actor?->id,
            'role' => $actor?->role,
            'event' => $event,
            'message' => $message,
            'data' => $data ?: null,
        ]);
    }
}

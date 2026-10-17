<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestEvent;
use App\Models\MaintenanceRequestEvidence;
use App\Models\User;
use App\Notifications\MaintenanceTaskNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Owns the accountable lifecycle of a maintenance task: who it is assigned to,
 * when work starts, what evidence is submitted, whether the landlord verifies
 * the completion, and what happens when it is returned for further work or
 * escalates as overdue.
 *
 * It never overwrites history. Every action appends an entry to the task's
 * immutable event ledger and to the shared audit log, so earlier completion
 * submissions survive a later rework, and reassignment / verification /
 * deadline decisions can always be reconstructed.
 *
 * Completion is a claim, not a confirmation: only a landlord verification
 * closes a task as done.
 */
class MaintenanceAccountabilityService
{
    public function __construct(protected AuditService $audit) {}

    // Assign (or clear) a caretaker and optionally set the deadline.
    public function assign(MaintenanceRequest $task, ?User $caretaker, User $actor, ?string $dueDate = null): MaintenanceRequest
    {
        return DB::transaction(function () use ($task, $caretaker, $actor, $dueDate) {
            $previous = $task->assigned_to;

            $data = [
                'assigned_to' => $caretaker?->id,
                'status' => $caretaker ? 'assigned' : 'pending',
                'returned_at' => null,
                'returned_by' => null,
                'return_reason' => null,
                'escalated_at' => null,
            ];

            if ($caretaker) {
                $data['assigned_at'] = now();
                $data['assigned_by'] = $actor->id;
            } else {
                $data['assigned_at'] = null;
                $data['assigned_by'] = null;
            }

            if ($dueDate !== null) {
                $data['due_date'] = $this->normalizeDueDate($dueDate);
            }

            $old = ['assigned_to' => $previous, 'due_date' => optional($task->due_date)->toDateString()];
            $task->update($data);

            $event = match (true) {
                $caretaker === null => MaintenanceRequestEvent::EVENT_UNASSIGNED,
                $previous !== null && $previous !== $caretaker->id => MaintenanceRequestEvent::EVENT_REASSIGNED,
                default => MaintenanceRequestEvent::EVENT_ASSIGNED,
            };

            $this->recordEvent($task, $event, $actor, $this->assignmentMessage($caretaker), [
                'from' => $previous,
                'to' => $caretaker?->id,
                'due_date' => optional($task->fresh()->due_date)->toDateString(),
            ]);

            $this->audit->record(
                'maintenance_task_assigned',
                'Maintenance task "'.$task->title.'" assigned to '.($caretaker?->name ?? 'nobody').'.',
                $old,
                ['assigned_to' => $caretaker?->id, 'due_date' => optional($task->fresh()->due_date)->toDateString()],
                $task,
                $actor
            );

            if ($caretaker) {
                $caretaker->notify(new MaintenanceTaskNotification($task->fresh(), 'assigned'));
            }

            return $task->fresh();
        });
    }

    // The caretaker acknowledges work has started.
    public function start(MaintenanceRequest $task, User $actor): MaintenanceRequest
    {
        return DB::transaction(function () use ($task, $actor) {
            $task->update([
                'status' => 'in_progress',
                'started_at' => $task->started_at ?? now(),
                'returned_at' => null,
                'returned_by' => null,
                'return_reason' => null,
            ]);

            $this->recordEvent($task, MaintenanceRequestEvent::EVENT_STARTED, $actor, 'Work started.');

            $this->audit->record(
                'maintenance_task_started',
                'Work started on maintenance task "'.$task->title.'".',
                [],
                ['status' => 'in_progress'],
                $task,
                $actor
            );

            return $task->fresh();
        });
    }

    // Submit completion for verification, optionally with supporting evidence.
    public function submitCompletion(MaintenanceRequest $task, User $actor, array $attributes, array $files = []): MaintenanceRequest
    {
        return DB::transaction(function () use ($task, $actor, $attributes, $files) {
            $task->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolution_notes' => $attributes['resolution_notes'] ?? $task->resolution_notes,
                'returned_at' => null,
                'returned_by' => null,
                'return_reason' => null,
                'escalated_at' => null,
            ]);

            $stored = $this->storeEvidence($task, $actor, $files, MaintenanceRequestEvidence::TYPE_COMPLETION);

            $this->recordEvent(
                $task,
                MaintenanceRequestEvent::EVENT_COMPLETION_SUBMITTED,
                $actor,
                $attributes['resolution_notes'] ?? 'Completion submitted for verification.',
                [
                    'resolved_at' => now()->toIso8601String(),
                    'evidence_count' => $stored,
                ]
            );

            $this->audit->record(
                'maintenance_completion_submitted',
                'Completion of maintenance task "'.$task->title.'" submitted for verification.',
                [],
                ['status' => 'resolved', 'evidence_count' => $stored],
                $task,
                $actor
            );

            if ($task->property?->landlord) {
                $task->property->landlord->notify(new MaintenanceTaskNotification($task->fresh(), 'submitted'));
            }

            return $task->fresh();
        });
    }

    // The landlord confirms the work is satisfactory. Only this closes a task.
    public function verify(MaintenanceRequest $task, User $actor, ?string $notes = null): MaintenanceRequest
    {
        if (! $task->isAwaitingVerification()) {
            abort(422, 'Only a task that has been submitted for verification can be verified.');
        }

        return DB::transaction(function () use ($task, $actor, $notes) {
            $task->update([
                'status' => 'closed',
                'verified_at' => now(),
                'verified_by' => $actor->id,
                'verification_notes' => $notes,
                'returned_at' => null,
                'returned_by' => null,
                'return_reason' => null,
            ]);

            $this->recordEvent($task, MaintenanceRequestEvent::EVENT_VERIFIED, $actor, $notes ?: 'Completion verified.', [
                'notes' => $notes,
            ]);

            $this->audit->record(
                'maintenance_task_verified',
                'Maintenance task "'.$task->title.'" verified complete.',
                [],
                ['status' => 'closed', 'verified_by' => $actor->id],
                $task,
                $actor
            );

            if ($task->assignedTo) {
                $task->assignedTo->notify(new MaintenanceTaskNotification($task->fresh(), 'verified'));
            }

            return $task->fresh();
        });
    }

    // Send a submitted task back for further work without erasing its history.
    public function returnForRework(MaintenanceRequest $task, User $actor, string $reason): MaintenanceRequest
    {
        if (! in_array($task->status, ['resolved', 'closed'], true)) {
            abort(422, 'Only a task submitted for verification can be returned for further work.');
        }

        return DB::transaction(function () use ($task, $actor, $reason) {
            $previousResolvedAt = optional($task->resolved_at)->toIso8601String();

            $task->update([
                'status' => 'in_progress',
                'resolved_at' => null,
                'verified_at' => null,
                'verified_by' => null,
                'verification_notes' => null,
                'returned_at' => now(),
                'returned_by' => $actor->id,
                'return_reason' => $reason,
            ]);

            $this->recordEvent($task, MaintenanceRequestEvent::EVENT_RETURNED, $actor, $reason, [
                'previous_resolved_at' => $previousResolvedAt,
                'reason' => $reason,
            ]);

            $this->audit->record(
                'maintenance_task_returned',
                'Maintenance task "'.$task->title.'" returned for further work.',
                ['status' => 'resolved'],
                ['status' => 'in_progress', 'reason' => $reason],
                $task,
                $actor
            );

            if ($task->assignedTo) {
                $task->assignedTo->notify(new MaintenanceTaskNotification($task->fresh(), 'returned'));
            }

            return $task->fresh();
        });
    }

    // Change a task's deadline, recording both the old and new value.
    public function changeDeadline(MaintenanceRequest $task, ?string $dueDate, User $actor): MaintenanceRequest
    {
        return DB::transaction(function () use ($task, $dueDate, $actor) {
            $old = optional($task->due_date)->toDateString();
            $new = $this->normalizeDueDate($dueDate);

            $task->update([
                'due_date' => $new,
                'escalated_at' => null,
            ]);

            $this->recordEvent($task, MaintenanceRequestEvent::EVENT_DEADLINE_CHANGED, $actor, 'Deadline updated.', [
                'from' => $old,
                'to' => optional($new)->toDateString(),
            ]);

            $this->audit->record(
                'maintenance_deadline_changed',
                'Deadline for maintenance task "'.$task->title.'" changed.',
                ['due_date' => $old],
                ['due_date' => optional($new)->toDateString()],
                $task,
                $actor
            );

            return $task->fresh();
        });
    }

    // Attach supporting files without changing status.
    public function addEvidence(MaintenanceRequest $task, User $actor, array $files, string $type = MaintenanceRequestEvidence::TYPE_COMPLETION): int
    {
        if ($files === []) {
            return 0;
        }

        return DB::transaction(function () use ($task, $actor, $files, $type) {
            $stored = $this->storeEvidence($task, $actor, $files, $type);

            $this->recordEvent($task, MaintenanceRequestEvent::EVENT_EVIDENCE_ADDED, $actor, 'Evidence added.', [
                'count' => $stored,
                'type' => $type,
            ]);

            $this->audit->record(
                'maintenance_evidence_added',
                $stored.' file(s) added to maintenance task "'.$task->title.'".',
                [],
                ['type' => $type, 'count' => $stored],
                $task,
                $actor
            );

            return $stored;
        });
    }

    // A caretaker can ask for a record to be corrected rather than editing it.
    public function requestCorrection(MaintenanceRequest $task, User $actor, string $message): MaintenanceRequestEvent
    {
        $event = $this->recordEvent($task, MaintenanceRequestEvent::EVENT_CORRECTION_REQUESTED, $actor, $message);

        $this->audit->record(
            'maintenance_correction_requested',
            'A correction was requested for maintenance task "'.$task->title.'".',
            [],
            ['message' => $message],
            $task,
            $actor
        );

        if ($task->property?->landlord) {
            $task->property->landlord->notify(new MaintenanceTaskNotification($task->fresh(), 'correction'));
        }

        return $event;
    }

    /**
     * Flag overdue assigned tasks so the landlord is aware. This is a neutral
     * signal: it never changes the task status, never penalises the caretaker
     * and is cleared whenever the deadline is changed or the task progresses.
     *
     * @return int number of tasks escalated
     */
    public function escalateOverdue(?Carbon $asOf = null): int
    {
        $asOf = $asOf ?? now();

        $tasks = MaintenanceRequest::query()
            ->overdue($asOf)
            ->whereNotNull('assigned_to')
            ->whereNull('escalated_at')
            ->with(['property.landlord', 'assignedTo', 'unit'])
            ->get();

        foreach ($tasks as $task) {
            $task->update(['escalated_at' => now()]);

            $this->recordEvent($task, MaintenanceRequestEvent::EVENT_ESCALATED, null, 'Task is past its deadline.', [
                'due_date' => optional($task->due_date)->toDateString(),
            ]);

            $this->audit->record(
                'maintenance_task_escalated',
                'Maintenance task "'.$task->title.'" is overdue.',
                [],
                ['due_date' => optional($task->due_date)->toDateString(), 'assigned_to' => $task->assigned_to],
                $task,
                $task->property?->landlord
            );

            if ($task->property?->landlord) {
                $task->property->landlord->notify(new MaintenanceTaskNotification($task->fresh(), 'overdue'));
            }
        }

        return $tasks->count();
    }

    // Append an entry to the task's immutable ledger.
    public function recordEvent(MaintenanceRequest $task, string $event, ?User $actor, ?string $message = null, array $data = []): MaintenanceRequestEvent
    {
        return $task->events()->create([
            'user_id' => $actor?->id,
            'role' => $actor?->role,
            'event' => $event,
            'message' => $message,
            'data' => $data !== [] ? $data : null,
        ]);
    }

    /**
     * Persist uploaded files to a private disk. Returns the number stored.
     *
     * @param  array<int, UploadedFile>  $files
     */
    protected function storeEvidence(MaintenanceRequest $task, User $actor, array $files, string $type): int
    {
        $count = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('maintenance/'.$task->id, 'local');

            $task->evidence()->create([
                'user_id' => $actor->id,
                'type' => $type,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            $count++;
        }

        return $count;
    }

    protected function normalizeDueDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }

    protected function assignmentMessage(?User $caretaker): string
    {
        return $caretaker
            ? 'Task assigned to '.$caretaker->name.'.'
            : 'Task returned to the unassigned pool.';
    }
}

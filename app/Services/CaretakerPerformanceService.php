<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestEvent;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds an evidence-based work-history summary for a caretaker.
 *
 * Every figure is derived from the maintenance task ledger and always carries
 * the period it covers and the sample size it is based on. It never collapses
 * to a single score, and it never ranks, penalises, suspends or dismisses a
 * caretaker — a deadline that was legitimately extended simply counts against
 * the current deadline, and rework is reported transparently rather than as a
 * punishment.
 */
class CaretakerPerformanceService
{
    /**
     * @param  array{from?: string|null, to?: string|null, property_ids?: array<int, int>|null}  $filters
     * @return array<string, mixed>
     */
    public function summary(User $caretaker, array $filters = []): array
    {
        $from = $this->parseDate($filters['from'] ?? null);
        $to = $this->parseDate($filters['to'] ?? null);
        $propertyIds = $filters['property_ids'] ?? null;

        $tasks = $this->query($caretaker, $from, $to, $propertyIds)
            ->with(['property', 'unit'])
            ->get();

        return $this->metrics($caretaker, $tasks, $from, $to);
    }

    /**
     * @param  array{from?: string|null, to?: string|null, property_ids?: array<int, int>|null}  $filters
     */
    protected function query(User $caretaker, ?Carbon $from, ?Carbon $to, ?array $propertyIds)
    {
        $query = MaintenanceRequest::query()->where('assigned_to', $caretaker->id);

        if ($propertyIds !== null) {
            $query->whereIn('property_id', $propertyIds);
        }

        // Filter on the moment the task entered the caretaker's queue, falling
        // back to creation time for tasks assigned before this feature existed.
        if ($from !== null) {
            $query->whereRaw('COALESCE(assigned_at, created_at) >= ?', [$from->startOfDay()->toDateTimeString()]);
        }

        if ($to !== null) {
            $query->whereRaw('COALESCE(assigned_at, created_at) <= ?', [$to->endOfDay()->toDateTimeString()]);
        }

        return $query;
    }

    /**
     * @param  Collection<int, MaintenanceRequest>  $tasks
     * @return array<string, mixed>
     */
    protected function metrics(User $caretaker, Collection $tasks, ?Carbon $from, ?Carbon $to): array
    {
        $completed = $tasks->filter(fn (MaintenanceRequest $t) => $t->resolved_at !== null || $t->verified_at !== null);
        $verified = $tasks->filter(fn (MaintenanceRequest $t) => $t->verified_at !== null);
        $open = $tasks->filter(fn (MaintenanceRequest $t) => $t->isOpen());
        $overdue = $open->filter(fn (MaintenanceRequest $t) => $t->isOverdue());
        $awaiting = $tasks->filter(fn (MaintenanceRequest $t) => $t->isAwaitingVerification());

        $onTime = $completed->filter(fn (MaintenanceRequest $t) => $this->completedOnTime($t));
        $late = $completed->filter(fn (MaintenanceRequest $t) => $this->completedLate($t));

        // Rework is counted from the immutable ledger, so it survives a reopen.
        $reworkTaskIds = MaintenanceRequestEvent::query()
            ->whereIn('maintenance_request_id', $tasks->pluck('id'))
            ->where('event', MaintenanceRequestEvent::EVENT_RETURNED)
            ->pluck('maintenance_request_id')
            ->unique();

        $responseSamples = $tasks
            ->filter(fn (MaintenanceRequest $t) => $t->assigned_at !== null && $t->started_at !== null)
            ->map(fn (MaintenanceRequest $t) => $t->assigned_at->diffInMinutes($t->started_at) / 60);

        $resolutionSamples = $completed
            ->filter(fn (MaintenanceRequest $t) => $t->assigned_at !== null && $t->resolved_at !== null)
            ->map(fn (MaintenanceRequest $t) => $t->assigned_at->diffInHours($t->resolved_at, false));

        return [
            'caretaker' => $caretaker,
            'period' => [
                'from' => optional($from)->toDateString(),
                'to' => optional($to)->toDateString(),
            ],
            'sample_size' => $tasks->count(),
            'assigned' => $tasks->count(),
            'open' => $open->count(),
            'overdue_open' => $overdue->count(),
            'completed' => $completed->count(),
            'verified' => $verified->count(),
            'awaiting_verification' => $awaiting->count(),
            'completed_on_time' => $onTime->count(),
            'completed_late' => $late->count(),
            'on_time_rate' => $onTime->count() + $late->count() > 0
                ? round(($onTime->count() / ($onTime->count() + $late->count())) * 100, 1)
                : null,
            'avg_response_hours' => $this->average($completed->merge($open)->map(fn (MaintenanceRequest $t) => $this->responseHours($t))),
            'avg_resolution_hours' => $this->average($completed->map(fn (MaintenanceRequest $t) => $this->resolutionHours($t))),
            'tasks_returned_for_rework' => $reworkTaskIds->count(),
            'first_time_right_rate' => $completed->count() > 0
                ? round((($completed->count() - $completed->whereIn('id', $reworkTaskIds)->count()) / $completed->count()) * 100, 1)
                : null,
            'by_property' => $this->byProperty($tasks),
            'period_label' => $this->periodLabel($from, $to),
            'caretaker_name' => $caretaker->name,
        ];
    }

    /**
     * @param  Collection<int, MaintenanceRequest>  $tasks
     * @return array<int, array<string, mixed>>
     */
    protected function byProperty(Collection $tasks): array
    {
        return $tasks
            ->groupBy('property_id')
            ->map(function (Collection $group) {
                $property = $group->first()->property;

                return [
                    'property_id' => $group->first()->property_id,
                    'property' => $property?->name ?? 'Unknown',
                    'assigned' => $group->count(),
                    'open' => $group->filter(fn (MaintenanceRequest $t) => $t->isOpen())->count(),
                    'overdue' => $group->filter(fn (MaintenanceRequest $t) => $t->isOverdue())->count(),
                    'completed' => $group->filter(fn (MaintenanceRequest $t) => $t->resolved_at !== null || $t->verified_at !== null)->count(),
                ];
            })
            ->values()
            ->all();
    }

    protected function completedOnTime(MaintenanceRequest $task): bool
    {
        if ($task->due_date === null || $task->resolved_at === null) {
            return false;
        }

        return $task->resolved_at->startOfDay()->lte($task->due_date->startOfDay());
    }

    protected function completedLate(MaintenanceRequest $task): bool
    {
        if ($task->due_date === null || $task->resolved_at === null) {
            return false;
        }

        return $task->resolved_at->startOfDay()->gt($task->due_date->startOfDay());
    }

    protected function responseHours(MaintenanceRequest $task): ?float
    {
        if ($task->assigned_at === null || $task->started_at === null) {
            return null;
        }

        return round($task->assigned_at->diffInMinutes($task->started_at) / 60, 1);
    }

    protected function resolutionHours(MaintenanceRequest $task): ?float
    {
        if ($task->assigned_at === null || $task->resolved_at === null) {
            return null;
        }

        return round($task->assigned_at->diffInMinutes($task->resolved_at) / 60, 1);
    }

    /**
     * @param  Collection<int, float|null>  $values
     */
    protected function average(Collection $values): ?float
    {
        $clean = $values->filter(fn ($value) => $value !== null);

        if ($clean->isEmpty()) {
            return null;
        }

        return round($clean->avg(), 1);
    }

    protected function periodLabel(?Carbon $from, ?Carbon $to): string
    {
        return match (true) {
            $from !== null && $to !== null => $from->format('d M Y').' – '.$to->format('d M Y'),
            $from !== null => 'From '.$from->format('d M Y'),
            $to !== null => 'Up to '.$to->format('d M Y'),
            default => 'All time',
        };
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    /**
     * A neutral portfolio-level view for the landlord dashboard.
     *
     * @return array<string, int>
     */
    public function landlordOverview(User $landlord): array
    {
        $propertyIds = Property::where('user_id', $landlord->id)->pluck('id');

        $base = MaintenanceRequest::query()->whereIn('property_id', $propertyIds);

        return [
            'unassigned' => (clone $base)->whereNull('assigned_to')->unresolved()->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'escalated' => (clone $base)->whereNotNull('escalated_at')->count(),
            'awaiting_verification' => (clone $base)->awaitingVerification()->count(),
            'returned_for_rework' => (clone $base)->whereNotNull('returned_at')->count(),
            'caretakers' => User::where('role', 'caretaker')
                ->whereHas('caretakerProperties', fn ($q) => $q->whereIn('properties.id', $propertyIds))
                ->count(),
        ];
    }
}

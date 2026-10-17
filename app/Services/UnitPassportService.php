<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\MoveOutInspection;
use App\Models\Unit;
use App\Models\UnitConditionAssessment;
use App\Models\UnitInspection;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates the Property Passport overview for a single unit. All figures are
 * derived from existing records; nothing is invented when data is missing.
 */
class UnitPassportService
{
    public function __construct(protected UnitTimelineService $timeline) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Unit $unit): array
    {
        $activeTenant = $unit->tenant;
        $lastMoveOut = $unit->tenants()
            ->whereNotNull('move_out_date')
            ->orderByDesc('move_out_date')
            ->first();

        return [
            'active_tenant' => $activeTenant,
            'last_move_out' => $lastMoveOut,
            'move_in_date' => $activeTenant?->move_in_date,
            'open_maintenance' => $this->openMaintenance($unit),
            'last_inspection' => $this->lastInspection($unit),
            'last_document' => $unit->documents()->latest()->first(),
            'last_assessment' => $unit->conditionAssessments()->latest('assessed_at')->first(),
            'counts' => [
                'inspections' => $unit->inspections()->count(),
                'documents' => $unit->documents()->count(),
                'maintenance' => $unit->maintenanceRequests()->count(),
                'tenancies' => $unit->tenants()->count(),
            ],
        ];
    }

    /**
     * A factual condition summary. Missing data is reported as such and never
     * replaced with an invented rating.
     *
     * @return array<string, mixed>
     */
    public function conditionSummary(Unit $unit): array
    {
        $lastInspection = $this->lastInspection($unit);
        $openMaintenance = $this->openMaintenance($unit);
        $lastAssessment = $unit->conditionAssessments()->latest('assessed_at')->first();

        $outstandingInspections = UnitInspection::where('unit_id', $unit->id)
            ->where('status', UnitInspection::STATUS_DRAFT)
            ->count();

        $concerns = collect();
        if ($lastInspection instanceof UnitInspection && $lastInspection->isSubmitted()) {
            $concerns = $lastInspection->items
                ->filter(fn ($item) => in_array($item->condition, [
                    MoveOutInspection::CONDITION_NEEDS_ATTENTION,
                    MoveOutInspection::CONDITION_DAMAGED,
                ], true) && filled($item->comment))
                ->map(fn ($item) => $item->label.': '.$item->comment);
        }

        $lastRepair = $unit->maintenanceRequests()
            ->whereNotNull('resolved_at')
            ->orderByDesc('resolved_at')
            ->first();

        return [
            'last_inspection' => $lastInspection,
            'last_inspection_date' => $lastInspection?->inspection_date,
            'open_maintenance' => $openMaintenance,
            'last_repair' => $lastRepair,
            'outstanding_inspections' => $outstandingInspections,
            'concerns' => $concerns,
            'assessment' => $lastAssessment,
            'has_data' => $lastInspection !== null
                || $openMaintenance->isNotEmpty()
                || $outstandingInspections > 0
                || $lastAssessment instanceof UnitConditionAssessment,
        ];
    }

    /**
     * @return Collection<int, MaintenanceRequest>
     */
    public function openMaintenance(Unit $unit)
    {
        return $unit->maintenanceRequests()
            ->whereNotIn('status', ['resolved', 'closed'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function lastInspection(Unit $unit): ?UnitInspection
    {
        return UnitInspection::where('unit_id', $unit->id)
            ->where('status', UnitInspection::STATUS_SUBMITTED)
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->with('items')
            ->first();
    }

    /**
     * @return array{condition: string, label: string, badge: string}
     */
    public function occupancyBadge(Unit $unit): array
    {
        return match ($unit->status) {
            'occupied' => ['condition' => 'occupied', 'label' => 'Occupied', 'badge' => 'kp-badge-danger'],
            'reserved' => ['condition' => 'reserved', 'label' => 'Reserved', 'badge' => 'kp-badge-warning'],
            'vacant' => ['condition' => 'vacant', 'label' => 'Vacant', 'badge' => 'kp-badge-success'],
            default => ['condition' => $unit->status, 'label' => ucfirst((string) $unit->status), 'badge' => 'kp-badge-muted'],
        };
    }

    /**
     * A side-by-side comparison of a move-in inspection and the latest move-out
     * inspection for the same unit, matched by checklist label. Differences are
     * highlighted but never auto-attributed to the tenant.
     *
     * @return array<int, array<string, mixed>>
     */
    public function moveInMoveOutComparison(Unit $unit): array
    {
        $moveIn = $unit->inspections()
            ->where('type', UnitInspection::TYPE_MOVE_IN)
            ->where('status', UnitInspection::STATUS_SUBMITTED)
            ->latest('inspection_date')
            ->with('items')
            ->first();

        if ($moveIn === null) {
            return [];
        }

        $moveOut = MoveOutInspection::whereHas('moveOutRequest', function ($query) use ($unit) {
            $query->where('unit_id', $unit->id);
        })
            ->where('status', MoveOutInspection::STATUS_SUBMITTED)
            ->latest('completed_at')
            ->with('items')
            ->first();

        if ($moveOut === null) {
            return [];
        }

        $moveOutItems = $moveOut->items->keyBy('label');

        return $moveIn->items->map(function ($item) use ($moveOutItems) {
            $counterpart = $moveOutItems->get($item->label);

            return [
                'category' => $item->category,
                'label' => $item->label,
                'move_in_condition' => $item->condition,
                'move_in_label' => $item->conditionLabel(),
                'move_in_comment' => $item->comment,
                'move_out_condition' => $counterpart?->condition,
                'move_out_label' => $counterpart ? $counterpart->conditionLabel() : '—',
                'move_out_comment' => $counterpart?->comment,
                'changed' => $counterpart
                    && $counterpart->condition !== null
                    && $item->condition !== null
                    && $counterpart->condition !== $item->condition,
            ];
        })->all();
    }

    public function recentActivity(Unit $unit, ?User $viewer, int $limit = 8)
    {
        return $this->timeline->build($unit, [], $viewer)->take($limit);
    }

    public function today(): Carbon
    {
        return now()->startOfDay();
    }
}

<?php

namespace App\Services;

use App\Models\MoveOutInspection;
use App\Models\MoveOutInspectionItem;
use App\Models\MoveOutRequest;
use App\Models\UnitInspection;

/**
 * Builds the side-by-side evidence comparison of the move-in baseline and the
 * move-out inspection for a single Notice to Vacate. Differences are reported
 * factually; the service never attributes damage to a tenant.
 */
class MoveOutResolutionService
{
    /**
     * @return array<string, mixed>
     */
    public function compare(MoveOutRequest $request): array
    {
        $moveIn = UnitInspection::where('unit_id', $request->unit_id)
            ->where('type', UnitInspection::TYPE_MOVE_IN)
            ->where('status', UnitInspection::STATUS_SUBMITTED)
            ->where(function ($query) use ($request) {
                $query->where('tenant_id', $request->tenant_id)->orWhereNull('tenant_id');
            })
            ->latest('inspection_date')
            ->latest('id')
            ->with(['items', 'inspectedBy'])
            ->first();

        $moveOut = $request->inspections()
            ->where('status', MoveOutInspection::STATUS_SUBMITTED)
            ->latest('completed_at')
            ->latest('id')
            ->with(['items', 'caretaker'])
            ->first();

        $moveInItems = $moveIn ? $moveIn->items->keyBy('label') : collect();
        $moveOutItems = $moveOut ? $moveOut->items->keyBy('label') : collect();

        $labels = $moveInItems->keys()
            ->merge($moveOutItems->keys())
            ->unique()
            ->values();

        $rows = $labels->map(function (string $label) use ($moveInItems, $moveOutItems) {
            $in = $moveInItems->get($label);
            $out = $moveOutItems->get($label);

            return [
                'category' => $out?->category ?? $in?->category,
                'label' => $label,
                'move_in_condition' => $in?->condition,
                'move_in_label' => $in ? $in->conditionLabel() : '—',
                'move_in_comment' => $in?->comment,
                'move_out_condition' => $out?->condition,
                'move_out_label' => $out ? $out->conditionLabel() : '—',
                'move_out_comment' => $out?->comment,
                'nature' => $out?->nature,
                'nature_label' => $out ? $out->natureLabel() : '—',
                'changed' => $in && $out
                    && $in->condition !== null
                    && $out->condition !== null
                    && $in->condition !== $out->condition,
                'missing_baseline' => $in === null,
            ];
        })->all();

        return [
            'has_baseline' => $moveIn !== null,
            'move_in' => $moveIn,
            'move_out' => $moveOut,
            'rows' => $rows,
            'new_damage_count' => collect($rows)
                ->where('nature', MoveOutInspectionItem::NATURE_NEW_DAMAGE)
                ->count(),
            'flag' => $moveIn === null
                ? 'No move-in baseline evidence exists for this tenancy.'
                : null,
            'missing_evidence' => $this->missingEvidence($moveIn, $moveOut),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function missingEvidence(?UnitInspection $moveIn, ?MoveOutInspection $moveOut): array
    {
        $notes = [];

        if ($moveIn === null) {
            $notes[] = 'No move-in inspection baseline exists for this tenancy, so differences cannot be verified.';
        } else {
            if (empty($moveIn->photos)) {
                $notes[] = 'The move-in baseline has no photographs.';
            }
            if (! $moveIn->isAcknowledged()) {
                $notes[] = 'The tenant has not acknowledged the move-in baseline.';
            }
        }

        if ($moveOut === null) {
            $notes[] = 'No submitted move-out inspection exists yet.';
        } elseif (empty($moveOut->photos)) {
            $notes[] = 'The move-out inspection has no photographs.';
        }

        return $notes;
    }
}

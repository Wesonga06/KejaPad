<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UnitInspectionService
{
    public function __construct(protected AuditService $audit) {}

    /**
     * Create a draft inspection for a unit and seed the factual checklist.
     * The checklist vocabulary is shared with the existing move-out inspections.
     */
    public function create(Unit $unit, string $type, User $actor, array $data = []): UnitInspection
    {
        return DB::transaction(function () use ($unit, $type, $actor, $data) {
            $inspection = UnitInspection::create([
                'unit_id' => $unit->id,
                'property_id' => $unit->property_id,
                'tenant_id' => $data['tenant_id'] ?? $unit->tenant?->id,
                'type' => $type,
                'status' => UnitInspection::STATUS_DRAFT,
                'inspection_date' => $data['inspection_date'] ?? now()->toDateString(),
                'inspected_by' => $data['inspected_by'] ?? $actor->id,
                'created_by' => $actor->id,
            ]);

            foreach (UnitInspection::checklist() as $category => $group) {
                foreach ($group['items'] as $label) {
                    // Utility meter readings are captured as dedicated fields, so
                    // skip them in the generic checklist to avoid duplication.
                    if ($category === 'utilities') {
                        continue;
                    }

                    $inspection->items()->create([
                        'category' => $category,
                        'label' => $label,
                    ]);
                }
            }

            $this->audit->record(
                'unit_inspection_created',
                'Draft '.$inspection->typeLabel().' started for unit '.$unit->unit_number.'.',
                [],
                ['inspection_id' => $inspection->id, 'type' => $type],
                $unit,
                $actor
            );

            return $inspection->fresh(['items']);
        });
    }

    /**
     * Save draft content. Does not change the inspection status.
     */
    public function saveDraft(UnitInspection $inspection, array $data, User $actor): UnitInspection
    {
        return DB::transaction(function () use ($inspection, $data) {
            $inspection->update($this->contentAttributes($inspection, $data));
            $this->saveItems($inspection, $data['items'] ?? []);

            return $inspection->fresh(['items']);
        });
    }

    /**
     * Submit an inspection. Submitted inspections become the factual record and
     * are not overwritten by later drafts; edits after submission create a new
     * inspection instead.
     */
    public function submit(UnitInspection $inspection, array $data, User $actor): UnitInspection
    {
        return DB::transaction(function () use ($inspection, $data, $actor) {
            $attributes = $this->contentAttributes($inspection, $data);
            $attributes['status'] = UnitInspection::STATUS_SUBMITTED;
            $attributes['inspected_by'] = $inspection->inspected_by ?? $actor->id;

            $inspection->update($attributes);
            $this->saveItems($inspection, $data['items'] ?? []);

            $this->audit->record(
                'unit_inspection_submitted',
                $inspection->typeLabel().' submitted for unit '
                    .($inspection->unit?->unit_number ?? '—').'.',
                [],
                ['inspection_id' => $inspection->id, 'recommendation' => $inspection->recommendation],
                $inspection->unit,
                $actor
            );

            // A submitted move-in inspection satisfies the final turnover stage.
            if ($inspection->type === UnitInspection::TYPE_MOVE_IN && $inspection->unit) {
                app(TurnoverService::class)->syncForUnit($inspection->unit, $actor);
            }

            return $inspection->fresh(['items']);
        });
    }

    /**
     * Record a tenant's acknowledgement of an inspection. Acknowledgement is
     * confirmation of receipt only and never a waiver of legal rights.
     */
    public function acknowledge(UnitInspection $inspection, User $tenantUser, array $data): UnitInspection
    {
        $inspection->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $tenantUser->id,
            'acknowledgement_name' => $data['acknowledgement_name'] ?? $tenantUser->name,
            'acknowledgement_note' => $data['acknowledgement_note'] ?? null,
        ]);

        $this->audit->record(
            'unit_inspection_acknowledged',
            'Tenant acknowledged '.$inspection->typeLabel().'.',
            [],
            ['inspection_id' => $inspection->id],
            $inspection->unit,
            $tenantUser
        );

        return $inspection->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    protected function contentAttributes(UnitInspection $inspection, array $data): array
    {
        return [
            'inspection_date' => $data['inspection_date'] ?? $inspection->inspection_date,
            'keys_returned' => $data['keys_returned'] ?? null,
            'keys_notes' => $data['keys_notes'] ?? null,
            'electricity_meter' => $data['electricity_meter'] ?? null,
            'water_meter' => $data['water_meter'] ?? null,
            'cleanliness' => $data['cleanliness'] ?? null,
            'general_notes' => $data['general_notes'] ?? null,
            'recommendation' => $data['recommendation'] ?? null,
            'photos' => array_values(array_merge(
                $inspection->photos ?? [],
                $this->storePhotos($inspection, $data['photos'] ?? []),
            )),
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     * @return array<int, string>
     */
    protected function storePhotos(UnitInspection $inspection, array $photos): array
    {
        $stored = [];

        foreach ($photos as $photo) {
            if ($photo && $photo->isValid()) {
                $stored[] = $photo->store('passport/inspections/'.$inspection->unit_id, 'local');
            }
        }

        return $stored;
    }

    protected function saveItems(UnitInspection $inspection, array $items): void
    {
        foreach ($items as $itemId => $values) {
            $item = $inspection->items()->whereKey($itemId)->first();

            if ($item instanceof UnitInspectionItem) {
                $item->update([
                    'condition' => $values['condition'] ?? null,
                    'comment' => $values['comment'] ?? null,
                ]);
            }
        }
    }
}

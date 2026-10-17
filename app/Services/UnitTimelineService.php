<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MoveOutInspection;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitDocument;
use App\Models\UnitInspection;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Builds a Property Passport timeline for a unit.
 *
 * The timeline is computed from the existing source records (tenancies,
 * maintenance, inspections, documents, condition assessments and audited unit
 * changes) rather than being copied into a separate history table. This keeps a
 * single source of truth and avoids duplicate or contradictory entries.
 */
class UnitTimelineService
{
    public const CATEGORY_TENANCY = 'tenancy';

    public const CATEGORY_INSPECTION = 'inspection';

    public const CATEGORY_MAINTENANCE = 'maintenance';

    public const CATEGORY_DOCUMENT = 'document';

    public const CATEGORY_CONDITION = 'condition';

    public const CATEGORY_STATUS = 'status';

    /**
     * @param  array{category?: string|null, from?: string|null, to?: string|null}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function build(Unit $unit, array $filters = [], ?User $viewer = null): Collection
    {
        $events = collect();

        foreach ($this->tenancyEvents($unit, $viewer) as $event) {
            $events->push($event);
        }

        foreach ($this->inspectionEvents($unit, $viewer) as $event) {
            $events->push($event);
        }

        foreach ($this->moveOutInspectionEvents($unit, $viewer) as $event) {
            $events->push($event);
        }

        foreach ($this->maintenanceEvents($unit, $viewer) as $event) {
            $events->push($event);
        }

        foreach ($this->documentEvents($unit, $viewer) as $event) {
            $events->push($event);
        }

        foreach ($this->conditionEvents($unit, $viewer) as $event) {
            $events->push($event);
        }

        foreach ($this->statusEvents($unit, $viewer) as $event) {
            $events->push($event);
        }

        return $events
            ->filter(fn (array $event) => $this->matches($event, $filters))
            ->sortByDesc(fn (array $event) => $event['date']->getTimestamp())
            ->values();
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  array{category?: string|null, from?: string|null, to?: string|null}  $filters
     */
    protected function matches(array $event, array $filters): bool
    {
        if (! empty($filters['category']) && $event['category'] !== $filters['category']) {
            return false;
        }

        if (! empty($filters['from']) && $event['date']->lt(Carbon::parse($filters['from'])->startOfDay())) {
            return false;
        }

        if (! empty($filters['to']) && $event['date']->gt(Carbon::parse($filters['to'])->endOfDay())) {
            return false;
        }

        return true;
    }

    // A tenant only ever sees history that concerns their own tenancy; other
    // tenants' names, documents and maintenance are never exposed to them.
    protected function restrictForTenant(?User $viewer): bool
    {
        return $viewer !== null && $viewer->isTenant();
    }

    /**
     * @return array<int, int>
     */
    protected function viewerTenantIds(?User $viewer): array
    {
        if (! $this->restrictForTenant($viewer)) {
            return [];
        }

        return Tenant::where('user_id', $viewer->id)->pluck('id')->all();
    }

    protected function tenancyEvents(Unit $unit, ?User $viewer): Collection
    {
        $query = $unit->tenants();

        if ($this->restrictForTenant($viewer)) {
            $query->where('user_id', $viewer->id);
        }

        return $query
            ->orderBy('move_in_date')
            ->get()
            ->flatMap(function ($tenant) {
                $events = [];

                if ($tenant->move_in_date) {
                    $events[] = [
                        'category' => self::CATEGORY_TENANCY,
                        'type' => 'tenancy_move_in',
                        'title' => 'Tenant moved in',
                        'description' => $tenant->full_name.' began a tenancy.',
                        'status' => $tenant->is_active ? 'Active' : 'Ended',
                        'actor' => null,
                        'date' => Carbon::parse($tenant->move_in_date)->startOfDay(),
                        'icon' => 'bi-box-arrow-in-right',
                        'url' => null,
                    ];
                }

                if ($tenant->move_out_date) {
                    $events[] = [
                        'category' => self::CATEGORY_TENANCY,
                        'type' => 'tenancy_move_out',
                        'title' => 'Tenant moved out',
                        'description' => $tenant->full_name.' vacated the unit.',
                        'status' => 'Ended',
                        'actor' => null,
                        'date' => Carbon::parse($tenant->move_out_date)->startOfDay(),
                        'icon' => 'bi-box-arrow-right',
                        'url' => null,
                    ];
                }

                return $events;
            });
    }

    protected function inspectionEvents(Unit $unit, ?User $viewer): Collection
    {
        $query = $unit->inspections();

        if ($this->restrictForTenant($viewer)) {
            $ids = $this->viewerTenantIds($viewer);
            $query->where(fn ($q) => $q->whereIn('tenant_id', $ids)->orWhereNull('tenant_id'));
        }

        return $query
            ->latest('inspection_date')
            ->get()
            ->map(function (UnitInspection $inspection) use ($viewer) {
                return [
                    'category' => self::CATEGORY_INSPECTION,
                    'type' => 'unit_inspection_'.$inspection->type,
                    'title' => $inspection->typeLabel(),
                    'description' => 'Recorded by '.($inspection->inspectedBy->name ?? 'a caretaker').'.',
                    'status' => $inspection->statusLabel(),
                    'actor' => $inspection->inspectedBy->name ?? null,
                    'date' => Carbon::parse($inspection->inspection_date ?? $inspection->created_at),
                    'icon' => 'bi-clipboard-check',
                    'url' => $this->inspectionUrl($inspection, $viewer),
                ];
            });
    }

    protected function moveOutInspectionEvents(Unit $unit, ?User $viewer): Collection
    {
        return MoveOutInspection::whereHas('moveOutRequest', function ($query) use ($unit, $viewer) {
            $query->where('unit_id', $unit->id);

            if ($this->restrictForTenant($viewer)) {
                $query->whereIn('tenant_id', $this->viewerTenantIds($viewer));
            }
        })
            ->with(['caretaker', 'moveOutRequest'])
            ->get()
            ->map(function (MoveOutInspection $inspection) {
                $date = $inspection->completed_at ?? $inspection->scheduled_at ?? $inspection->created_at;

                return [
                    'category' => self::CATEGORY_INSPECTION,
                    'type' => 'move_out_inspection',
                    'title' => 'Move-out inspection',
                    'description' => 'Conducted by '.($inspection->caretaker->name ?? 'a caretaker').'.',
                    'status' => $inspection->statusLabel(),
                    'actor' => $inspection->caretaker->name ?? null,
                    'date' => Carbon::parse($date),
                    'icon' => 'bi-clipboard-x',
                    'url' => null,
                ];
            });
    }

    protected function maintenanceEvents(Unit $unit, ?User $viewer): Collection
    {
        $query = $unit->maintenanceRequests();

        if ($this->restrictForTenant($viewer)) {
            $query->whereIn('tenant_id', $this->viewerTenantIds($viewer));
        }

        return $query
            ->with(['assignedTo', 'tenant'])
            ->get()
            ->flatMap(function ($request) use ($viewer) {
                $url = $this->maintenanceUrl($request, $viewer);

                $events = [[
                    'category' => self::CATEGORY_MAINTENANCE,
                    'type' => 'maintenance_reported',
                    'title' => 'Maintenance issue reported',
                    'description' => ($request->title ?: $request->categoryLabel()).'.',
                    'status' => ucfirst(str_replace('_', ' ', $request->status)),
                    'actor' => $request->tenant->full_name ?? null,
                    'date' => Carbon::parse($request->created_at),
                    'icon' => 'bi-tools',
                    'url' => $url,
                ]];

                if ($request->resolved_at) {
                    $events[] = [
                        'category' => self::CATEGORY_MAINTENANCE,
                        'type' => 'maintenance_completed',
                        'title' => 'Maintenance work completed',
                        'description' => $request->title ?: 'Repair completed.',
                        'status' => 'Completed',
                        'actor' => $request->assignedTo->name ?? null,
                        'date' => Carbon::parse($request->resolved_at),
                        'icon' => 'bi-check2-circle',
                        'url' => $url,
                    ];
                }

                return $events;
            });
    }

    protected function documentEvents(Unit $unit, ?User $viewer): Collection
    {
        $query = $unit->documents()->with('uploadedBy');

        // Tenants never see the existence of landlord-only documents.
        if ($this->restrictForTenant($viewer)) {
            $query->where('visibility', UnitDocument::VISIBILITY_TENANT);
        }

        return $query
            ->get()
            ->map(function ($document) {
                return [
                    'category' => self::CATEGORY_DOCUMENT,
                    'type' => 'document_uploaded',
                    'title' => 'Document uploaded',
                    'description' => $document->title.' ('.$document->typeLabel().').',
                    'status' => null,
                    'actor' => $document->uploadedBy->name ?? null,
                    'date' => Carbon::parse($document->created_at),
                    'icon' => 'bi-file-earmark-text',
                    'url' => null,
                ];
            });
    }

    protected function conditionEvents(Unit $unit, ?User $viewer): Collection
    {
        // Subjective condition assessments are internal to the landlord/caretaker
        // team and are not surfaced to tenants.
        if ($this->restrictForTenant($viewer)) {
            return collect();
        }

        return $unit->conditionAssessments()
            ->with(['assessedBy', 'createdBy'])
            ->get()
            ->map(function ($assessment) {
                return [
                    'category' => self::CATEGORY_CONDITION,
                    'type' => 'condition_assessment',
                    'title' => 'Property condition assessment',
                    'description' => $assessment->summary,
                    'status' => $assessment->ratingLabel(),
                    'actor' => $assessment->assessedBy->name ?? $assessment->createdBy->name ?? null,
                    'date' => Carbon::parse($assessment->assessed_at ?? $assessment->created_at),
                    'icon' => 'bi-clipboard-heart',
                    'url' => null,
                ];
            });
    }

    protected function statusEvents(Unit $unit, ?User $viewer): Collection
    {
        // Unit status changes are operational records kept from tenants.
        if ($this->restrictForTenant($viewer)) {
            return collect();
        }

        $morph = $unit->getMorphClass();

        return AuditLog::where('auditable_type', $morph)
            ->where('auditable_id', $unit->id)
            ->with('user')
            ->get()
            ->map(function ($log) {
                return [
                    'category' => self::CATEGORY_STATUS,
                    'type' => 'unit_status_change',
                    'title' => 'Unit status change',
                    'description' => $log->description ?: 'Unit status updated.',
                    'status' => null,
                    'actor' => $log->user->name ?? null,
                    'date' => Carbon::parse($log->created_at),
                    'icon' => 'bi-arrow-repeat',
                    'url' => null,
                ];
            });
    }

    protected function inspectionUrl(UnitInspection $inspection, ?User $viewer): ?string
    {
        if ($viewer === null) {
            return null;
        }

        $name = match (true) {
            $viewer->isLandlord() => 'landlord.passport.inspections.show',
            $viewer->isCaretaker() => 'caretaker.passport.inspections.show',
            default => null,
        };

        if ($name === null || ! Route::has($name)) {
            return null;
        }

        // Only link when the viewer may actually open the inspection.
        if (! $viewer->can('view', $inspection)) {
            return null;
        }

        return route($name, $inspection);
    }

    protected function maintenanceUrl($request, ?User $viewer): ?string
    {
        if ($viewer === null || ! $viewer->can('view', $request)) {
            return null;
        }

        $name = match (true) {
            $viewer->isLandlord() => 'landlord.maintenance.show',
            $viewer->isCaretaker() => 'caretaker.maintenance.show',
            default => null,
        };

        if ($name === null || ! Route::has($name)) {
            return null;
        }

        return route($name, $request);
    }
}

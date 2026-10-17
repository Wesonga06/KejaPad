<?php

namespace App\Http\Controllers;

use App\Http\Requests\Passport\AcknowledgeUnitInspectionRequest;
use App\Models\Unit;
use App\Models\UnitConditionAssessment;
use App\Models\UnitDocument;
use App\Models\UnitInspection;
use App\Services\UnitInspectionService;
use App\Services\UnitPassportService;
use App\Services\UnitTimelineService;
use Illuminate\Http\Request;

class TenantPassportController extends Controller
{
    public function __construct(
        protected UnitPassportService $passport,
        protected UnitTimelineService $timeline,
        protected UnitInspectionService $inspections,
    ) {}

    // The tenant's own unit passport, limited to tenancy-related information.
    public function show(Request $request)
    {
        $tenant = $request->user()->tenantProfile;

        if ($tenant === null) {
            abort(404);
        }

        $unit = $tenant->unit;

        if ($unit === null) {
            abort(404);
        }

        $this->authorize('viewPassport', $unit);

        $viewer = $request->user();

        $filters = [
            'category' => $request->string('category')->toString() ?: null,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ];

        // Tenants only see documents explicitly shared with them.
        $documents = $unit->documents()
            ->where('visibility', UnitDocument::VISIBILITY_TENANT)
            ->with('uploadedBy')
            ->latest()
            ->get();

        $inspections = $unit->inspections()
            ->where(function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)
                    ->orWhereNull('tenant_id');
            })
            ->with(['inspectedBy'])
            ->latest('inspection_date')
            ->get();

        $tab = $request->string('tab')->toString() ?: 'overview';

        return view('passport.show', [
            'mode' => 'tenant',
            'property' => $unit->property,
            'unit' => $unit,
            'overview' => $this->passport->overview($unit),
            'condition' => $this->passport->conditionSummary($unit),
            'comparison' => [],
            'timeline' => $this->timeline->build($unit, $filters, $viewer),
            'recentActivity' => $this->passport->recentActivity($unit, $viewer, 6),
            'inspections' => $inspections,
            'documents' => $documents,
            'maintenance' => $unit->maintenanceRequests()->where('tenant_id', $tenant->id)->latest()->get(),
            'assessments' => collect(),
            'filters' => $filters,
            'tab' => $tab,
            'tenants' => collect([$tenant]),
            'documentTypes' => UnitDocument::types(),
            'documentVisibilities' => UnitDocument::visibilities(),
            'conditionRatings' => UnitConditionAssessment::ratings(),
            'inspectionTypes' => [],
        ]);
    }

    public function acknowledge(AcknowledgeUnitInspectionRequest $request, UnitInspection $inspection)
    {
        $this->inspections->acknowledge($inspection, $request->user(), $request->validated());

        return redirect()
            ->route('tenant.passport.show', ['tab' => 'inspections'])
            ->with('success', 'Thank you. Your acknowledgement has been recorded. This confirms receipt only and does not waive any rights.');
    }
}

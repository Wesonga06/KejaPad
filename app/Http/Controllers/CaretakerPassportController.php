<?php

namespace App\Http\Controllers;

use App\Http\Requests\Passport\StoreUnitDocumentRequest;
use App\Http\Requests\Passport\StoreUnitInspectionRequest;
use App\Http\Requests\Passport\SubmitUnitInspectionRequest;
use App\Http\Requests\Passport\UpdateUnitInspectionRequest;
use App\Models\MoveOutInspection;
use App\Models\Unit;
use App\Models\UnitConditionAssessment;
use App\Models\UnitDocument;
use App\Models\UnitInspection;
use App\Services\UnitInspectionService;
use App\Services\UnitPassportService;
use App\Services\UnitTimelineService;
use Illuminate\Http\Request;

class CaretakerPassportController extends Controller
{
    public function __construct(
        protected UnitPassportService $passport,
        protected UnitTimelineService $timeline,
        protected UnitInspectionService $inspections,
    ) {}

    // Units across the caretaker's assigned properties.
    public function index()
    {
        $propertyIds = auth()->user()->caretakerProperties()->pluck('properties.id');

        $units = Unit::whereIn('property_id', $propertyIds)
            ->with('property', 'tenant')
            ->withCount([
                'inspections as inspections_count' => fn ($q) => $q->where('status', UnitInspection::STATUS_SUBMITTED),
                'maintenanceRequests as open_maintenance_count' => fn ($q) => $q->whereNotIn('status', ['resolved', 'closed']),
            ])
            ->orderBy('property_id')
            ->orderBy('unit_number')
            ->get();

        return view('caretaker.passport.index', compact('units'));
    }

    public function show(Request $request, Unit $unit)
    {
        $this->authorize('viewPassport', $unit);

        return $this->render($request, $unit, 'caretaker');
    }

    public function storeInspection(StoreUnitInspectionRequest $request, Unit $unit)
    {
        $inspection = $this->inspections->create($unit, $request->type, $request->user(), [
            'inspection_date' => $request->inspection_date ?? now()->toDateString(),
            'tenant_id' => $request->input('tenant_id'),
        ]);

        return redirect()
            ->route('caretaker.passport.inspections.show', $inspection)
            ->with('success', 'Draft inspection created. Complete the checklist and submit when ready.');
    }

    public function showInspection(UnitInspection $inspection)
    {
        $this->authorize('view', $inspection);

        $inspection->load(['items', 'unit.property', 'tenant', 'inspectedBy']);

        return view('passport.inspection', [
            'mode' => 'caretaker',
            'inspection' => $inspection,
            'unit' => $inspection->unit,
            'conditions' => MoveOutInspection::conditions(),
        ]);
    }

    public function updateInspection(UpdateUnitInspectionRequest $request, UnitInspection $inspection)
    {
        $this->inspections->saveDraft($inspection, $request->validated(), $request->user());

        return back()->with('success', 'Draft saved.');
    }

    public function submitInspection(SubmitUnitInspectionRequest $request, UnitInspection $inspection)
    {
        $this->inspections->submit($inspection, $request->validated(), $request->user());

        return redirect()
            ->route('caretaker.passport.show', $inspection->unit_id)
            ->with('success', 'Inspection submitted and added to the unit history.');
    }

    public function storeDocument(StoreUnitDocumentRequest $request, Unit $unit)
    {
        $file = $request->file('file');
        $path = $file->store('passport/documents/'.$unit->id, 'local');

        UnitDocument::create([
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'uploaded_by' => $request->user()->id,
            'title' => $request->title,
            'type' => $request->type,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            // Caretakers may only upload to the landlord-visible pool; they cannot
            // widen a document's visibility to the tenant.
            'visibility' => UnitDocument::VISIBILITY_LANDLORD,
            'note' => $request->note,
        ]);

        return redirect()
            ->route('caretaker.passport.show', [$unit, 'tab' => 'documents'])
            ->with('success', 'Document uploaded.');
    }

    protected function render(Request $request, Unit $unit, string $mode)
    {
        $viewer = $request->user();

        $overview = $this->passport->overview($unit);
        $condition = $this->passport->conditionSummary($unit);
        $comparison = $this->passport->moveInMoveOutComparison($unit);

        $filters = [
            'category' => $request->string('category')->toString() ?: null,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ];

        $timeline = $this->timeline->build($unit, $filters, $viewer);
        $recentActivity = $this->passport->recentActivity($unit, $viewer, 6);

        $inspections = $unit->inspections()->with(['inspectedBy', 'tenant'])->latest('inspection_date')->get();
        $documents = $unit->documents()->with('uploadedBy')->latest()->get();
        $maintenance = $unit->maintenanceRequests()->with(['assignedTo', 'tenant'])->latest()->get();
        $assessments = $unit->conditionAssessments()->with(['assessedBy', 'createdBy'])->latest('assessed_at')->get();

        $tab = $request->string('tab')->toString() ?: 'overview';

        return view('passport.show', [
            'mode' => $mode,
            'property' => $unit->property,
            'unit' => $unit,
            'overview' => $overview,
            'condition' => $condition,
            'comparison' => $comparison,
            'timeline' => $timeline,
            'recentActivity' => $recentActivity,
            'inspections' => $inspections,
            'documents' => $documents,
            'maintenance' => $maintenance,
            'assessments' => $assessments,
            'filters' => $filters,
            'tab' => $tab,
            'tenants' => $unit->tenants()->orderByDesc('is_active')->get(),
            'documentTypes' => UnitDocument::types(),
            'documentVisibilities' => UnitDocument::visibilities(),
            'conditionRatings' => UnitConditionAssessment::ratings(),
            'inspectionTypes' => [
                UnitInspection::TYPE_MOVE_IN => 'Move-in inspection',
                UnitInspection::TYPE_ROUTINE => 'Routine inspection',
            ],
        ]);
    }
}

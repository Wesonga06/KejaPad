<?php

namespace App\Http\Controllers;

use App\Http\Requests\Passport\StoreUnitConditionAssessmentRequest;
use App\Http\Requests\Passport\StoreUnitDocumentRequest;
use App\Http\Requests\Passport\StoreUnitInspectionRequest;
use App\Http\Requests\Passport\SubmitUnitInspectionRequest;
use App\Http\Requests\Passport\UpdateUnitInspectionRequest;
use App\Models\MoveOutInspection;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitConditionAssessment;
use App\Models\UnitDocument;
use App\Models\UnitInspection;
use App\Services\UnitInspectionService;
use App\Services\UnitPassportService;
use App\Services\UnitTimelineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyPassportController extends Controller
{
    public function __construct(
        protected UnitPassportService $passport,
        protected UnitTimelineService $timeline,
        protected UnitInspectionService $inspections,
    ) {}

    // List a property's units with a link into each unit's passport.
    public function index(Property $property)
    {
        $this->authorize('view', $property);

        $units = $property->units()
            ->with('tenant')
            ->withCount([
                'inspections as inspections_count' => fn ($q) => $q->where('status', UnitInspection::STATUS_SUBMITTED),
                'documents as documents_count',
                'maintenanceRequests as open_maintenance_count' => fn ($q) => $q->whereNotIn('status', ['resolved', 'closed']),
            ])
            ->orderBy('unit_number')
            ->get();

        return view('landlord.passport.index', compact('property', 'units'));
    }

    // Show the Property Passport for a single unit.
    public function show(Request $request, Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);
        $this->authorize('viewPassport', $unit);

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
            'mode' => 'landlord',
            'property' => $property,
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

    public function storeInspection(StoreUnitInspectionRequest $request, Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);

        $inspection = $this->inspections->create($unit, $request->type, $request->user(), [
            'inspection_date' => $request->inspection_date ?? now()->toDateString(),
            'tenant_id' => $request->input('tenant_id'),
        ]);

        return redirect()
            ->route('landlord.passport.inspections.show', $inspection)
            ->with('success', 'Draft inspection created. Complete the checklist and submit when ready.');
    }

    public function showInspection(UnitInspection $inspection)
    {
        $this->authorize('view', $inspection);

        $inspection->load(['items', 'unit.property', 'tenant', 'inspectedBy']);

        return view('passport.inspection', [
            'mode' => 'landlord',
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
            ->route('landlord.passport.show', [$inspection->property_id, $inspection->unit_id, 'tab' => 'inspections'])
            ->with('success', 'Inspection submitted and added to the unit history.');
    }

    public function storeCondition(StoreUnitConditionAssessmentRequest $request, Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);

        UnitConditionAssessment::create([
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'assessed_by' => $request->user()->id,
            'created_by' => $request->user()->id,
            'assessed_at' => $request->assessed_at ?? now()->toDateString(),
            'rating' => $request->input('rating'),
            'summary' => $request->summary,
        ]);

        return redirect()
            ->route('landlord.passport.show', [$property, $unit, 'tab' => 'overview'])
            ->with('success', 'Condition assessment recorded.');
    }

    public function storeDocument(StoreUnitDocumentRequest $request, Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);

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
            'visibility' => $request->visibility,
            'note' => $request->note,
        ]);

        return redirect()
            ->route('landlord.passport.show', [$property, $unit, 'tab' => 'documents'])
            ->with('success', 'Document uploaded.');
    }

    public function destroyDocument(UnitDocument $document)
    {
        $this->authorize('delete', $document);

        $unit = $document->unit;

        Storage::disk($document->disk)->delete($document->path);
        $document->delete();

        return redirect()
            ->route('landlord.passport.show', [$unit->property_id, $unit->id, 'tab' => 'documents'])
            ->with('success', 'Document removed.');
    }
}

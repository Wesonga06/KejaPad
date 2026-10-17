<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\UnitConditionAssessment;
use App\Models\UnitDocument;
use App\Models\UnitInspection;
use App\Services\UnitPassportService;
use App\Services\UnitTimelineService;
use Illuminate\Http\Request;

class PassportController extends Controller
{
    public function __construct(
        protected UnitPassportService $passport,
        protected UnitTimelineService $timeline,
    ) {}

    // Read-only oversight listing of every unit on the platform.
    public function index(Request $request)
    {
        $this->authorize('viewAny', UnitInspection::class);

        $query = Unit::with('property', 'tenant');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('unit_number', 'like', "%{$search}%")
                    ->orWhereHas('property', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }

        $units = $query->orderBy('property_id')->orderBy('unit_number')->paginate(20)->withQueryString();

        return view('super_admin.passport.index', compact('units'));
    }

    // Read-only view of a unit's passport for administrative oversight.
    public function show(Request $request, Unit $unit)
    {
        $this->authorize('viewPassport', $unit);

        $viewer = $request->user();

        $filters = [
            'category' => $request->string('category')->toString() ?: null,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ];

        return view('passport.show', [
            'mode' => 'super_admin',
            'property' => $unit->property,
            'unit' => $unit,
            'overview' => $this->passport->overview($unit),
            'condition' => $this->passport->conditionSummary($unit),
            'comparison' => $this->passport->moveInMoveOutComparison($unit),
            'timeline' => $this->timeline->build($unit, $filters, $viewer),
            'recentActivity' => $this->passport->recentActivity($unit, $viewer, 6),
            'inspections' => $unit->inspections()->with(['inspectedBy', 'tenant'])->latest('inspection_date')->get(),
            'documents' => $unit->documents()->with('uploadedBy')->latest()->get(),
            'maintenance' => $unit->maintenanceRequests()->with(['assignedTo', 'tenant'])->latest()->get(),
            'assessments' => $unit->conditionAssessments()->with(['assessedBy', 'createdBy'])->latest('assessed_at')->get(),
            'filters' => $filters,
            'tab' => $request->string('tab')->toString() ?: 'overview',
            'tenants' => $unit->tenants()->orderByDesc('is_active')->get(),
            'documentTypes' => UnitDocument::types(),
            'documentVisibilities' => UnitDocument::visibilities(),
            'conditionRatings' => UnitConditionAssessment::ratings(),
            'inspectionTypes' => [],
        ]);
    }
}

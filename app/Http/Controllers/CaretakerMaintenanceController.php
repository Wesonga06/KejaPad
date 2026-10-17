<?php

namespace App\Http\Controllers;

use App\Http\Requests\Maintenance\RequestMaintenanceCorrectionRequest;
use App\Http\Requests\Maintenance\StoreMaintenanceEvidenceRequest;
use App\Http\Requests\Maintenance\SubmitMaintenanceCompletionRequest;
use App\Http\Requests\Maintenance\UpdateCaretakerStatusRequest;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestEvidence;
use App\Services\MaintenanceAccountabilityService;
use App\Services\TurnoverService;
use Illuminate\Http\Request;

class CaretakerMaintenanceController extends Controller
{
    public function __construct(protected MaintenanceAccountabilityService $accountability) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $base = MaintenanceRequest::visibleToCaretaker(auth()->user());

        $counts = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'awaiting_verification' => (clone $base)->awaitingVerification()->count(),
            'returned' => (clone $base)->whereNotNull('returned_at')->count(),
        ];

        $query = (clone $base)->with(['tenant', 'property', 'unit', 'assignedTo']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->boolean('mine')) {
            $query->where('assigned_to', auth()->id());
        }

        if ($request->filled('scope')) {
            match ($request->scope) {
                'overdue' => $query->overdue(),
                'awaiting_verification' => $query->awaitingVerification(),
                'returned' => $query->whereNotNull('returned_at'),
                default => null,
            };
        }

        $requests = $query->latest()->paginate(15)->appends($request->query());

        $properties = auth()->user()->caretakerProperties()->orderBy('name')->get();

        return view('caretaker.maintenance.index', compact('requests', 'counts', 'properties'));
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);

        $maintenance->load(['events.user', 'evidence.user', 'assignedTo', 'returnedBy', 'verifiedBy', 'tenant', 'property', 'unit', 'turnover']);

        return view('caretaker.maintenance.show', compact('maintenance'));
    }

    // Legacy endpoint: acknowledge work has started (and accept a completion
    // submitted without files for backwards compatibility).
    public function updateStatus(UpdateCaretakerStatusRequest $request, MaintenanceRequest $maintenance)
    {
        if ($request->status === 'resolved') {
            $this->accountability->submitCompletion(
                $maintenance,
                auth()->user(),
                ['resolution_notes' => $request->resolution_notes]
            );
        } else {
            $this->accountability->start($maintenance, auth()->user());
        }

        $this->afterChange($maintenance->fresh());

        return back()->with('success', 'Status updated successfully.');
    }

    // Submit completion with optional supporting evidence.
    public function submitCompletion(SubmitMaintenanceCompletionRequest $request, MaintenanceRequest $maintenance)
    {
        if (in_array($maintenance->status, ['pending', 'assigned'], true)) {
            $this->accountability->start($maintenance, auth()->user());
        }

        $this->accountability->submitCompletion(
            $maintenance->fresh(),
            auth()->user(),
            ['resolution_notes' => $request->resolution_notes],
            $request->file('evidence', [])
        );

        $this->afterChange($maintenance->fresh());

        return back()->with('success', 'Completion submitted for the landlord\'s verification.');
    }

    public function addEvidence(StoreMaintenanceEvidenceRequest $request, MaintenanceRequest $maintenance)
    {
        $this->accountability->addEvidence(
            $maintenance,
            auth()->user(),
            $request->file('evidence', []),
            MaintenanceRequestEvidence::TYPE_COMPLETION
        );

        return back()->with('success', 'Evidence uploaded.');
    }

    // A caretaker can request a correction rather than editing a record.
    public function requestCorrection(RequestMaintenanceCorrectionRequest $request, MaintenanceRequest $maintenance)
    {
        $this->accountability->requestCorrection($maintenance, auth()->user(), $request->message);

        return back()->with('success', 'Correction request submitted.');
    }

    // Keep the coordinated turnover in step with the task's progress.
    protected function afterChange(MaintenanceRequest $maintenance): void
    {
        if ($maintenance->turnover_id) {
            app(TurnoverService::class)->syncForMaintenance($maintenance, auth()->user());
        }
    }
}

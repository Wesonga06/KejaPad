<?php

namespace App\Http\Controllers;

use App\Http\Requests\Turnover\AddTurnoverTaskRequest;
use App\Http\Requests\Turnover\AssignTurnoverTaskRequest;
use App\Http\Requests\Turnover\MarkReadyRequest;
use App\Http\Requests\Turnover\RecordHandoverRequest;
use App\Http\Requests\Turnover\VerifyTurnoverTaskRequest;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Turnover;
use App\Models\UnitInspection;
use App\Models\User;
use App\Services\TurnoverService;
use App\Services\UnitInspectionService;
use Illuminate\Http\Request;

class LandlordTurnoverController extends Controller
{
    public function __construct(protected TurnoverService $turnovers) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Turnover::class);

        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $base = Turnover::whereIn('property_id', $propertyIds);

        $query = clone $base;

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($propertyId = $request->integer('property_id')) {
            $query->where('property_id', $propertyId);
        }

        $turnovers = $query->with([
            'property',
            'unit',
            'outgoingTenant',
            'nextTenant',
            'moveOutRequest.inspection',
            'moveOutRequest.settlement',
        ])->latest('last_activity_at')->latest('id')->paginate(15)->withQueryString();

        $stats = [
            'open' => (clone $base)->whereNotIn('status', [
                Turnover::STATUS_CANCELLED,
                Turnover::STATUS_OCCUPIED,
            ])->count(),
            'ready' => (clone $base)->where('status', Turnover::STATUS_READY_FOR_LETTING)->count(),
            'repairs' => (clone $base)->where('status', Turnover::STATUS_REPAIRS_REQUIRED)->count(),
            'awaiting_verification' => (clone $base)->where('status', Turnover::STATUS_AWAITING_VERIFICATION)->count(),
        ];

        $properties = Property::where('user_id', auth()->id())->orderBy('name')->get();

        return view('landlord.turnovers.index', compact('turnovers', 'properties', 'stats'));
    }

    public function show(Turnover $turnover)
    {
        $this->authorize('view', $turnover);

        $turnover->load([
            'property',
            'unit',
            'outgoingTenant',
            'nextTenant',
            'readinessConfirmedBy',
            'handoverRecordedBy',
            'moveOutRequest.tenant',
            'moveOutRequest.inspection.items',
            'moveOutRequest.reviewedBy',
            'moveOutRequest.clearedBy',
            'moveOutRequest.settlement',
            'tasks.assignedTo',
            'tasks.verifiedBy',
            'events.user',
        ]);

        $checklist = $this->turnovers->checklist($turnover);
        $nextAction = $this->turnovers->nextAction($turnover);

        $deposit = $turnover->outgoingTenant?->securityDeposit;
        $settlement = $turnover->moveOutRequest?->settlement;
        $inspection = $turnover->moveOutRequest?->inspection;
        $incomingInspection = $turnover->incomingMoveInInspection();
        $caretakers = $turnover->property?->caretakers()->orderBy('name')->get() ?? collect();

        return view('landlord.turnovers.show', compact(
            'turnover',
            'checklist',
            'nextAction',
            'deposit',
            'settlement',
            'inspection',
            'incomingInspection',
            'caretakers'
        ));
    }

    public function addTask(AddTurnoverTaskRequest $request, Turnover $turnover)
    {
        $this->turnovers->addTask($turnover, $request->validated(), auth()->user());

        return back()->with('success', 'Turnover task added.');
    }

    public function assignTask(AssignTurnoverTaskRequest $request, Turnover $turnover, MaintenanceRequest $task)
    {
        $caretaker = $request->filled('assigned_to')
            ? User::find($request->integer('assigned_to'))
            : null;

        $this->turnovers->assignTask($turnover, $task, $caretaker, auth()->user());

        return back()->with('success', $caretaker
            ? 'Task assigned to '.$caretaker->name.'.'
            : 'Task unassigned.');
    }

    public function verifyTask(VerifyTurnoverTaskRequest $request, Turnover $turnover, MaintenanceRequest $task)
    {
        $this->turnovers->verifyTask($turnover, $task, auth()->user(), [
            'verification_notes' => $request->input('verification_notes'),
        ]);

        return back()->with('success', 'Completion verified.');
    }

    public function recordHandover(RecordHandoverRequest $request, Turnover $turnover)
    {
        $this->turnovers->recordHandover($turnover, [
            'handover_notes' => $request->input('handover_notes'),
            'keys_returned' => $request->boolean('keys_returned'),
        ], auth()->user());

        return back()->with('success', 'Handover recorded.');
    }

    public function markReady(MarkReadyRequest $request, Turnover $turnover)
    {
        $this->turnovers->markReady($turnover, auth()->user(), [
            'readiness_notes' => $request->input('readiness_notes'),
        ]);

        return back()->with('success', 'Unit confirmed ready for letting.');
    }

    public function beginOnboarding(Request $request, Turnover $turnover)
    {
        $this->authorize('onboard', $turnover);

        $this->turnovers->beginOnboarding($turnover, auth()->user());

        return redirect()
            ->route('landlord.tenants.create', [
                'property_id' => $turnover->property_id,
                'unit_id' => $turnover->unit_id,
            ])
            ->with('success', 'Begin the new tenancy. The unit is ready for letting.');
    }

    // Create or open the move-in inspection for the incoming tenancy.
    public function incomingInspection(Request $request, Turnover $turnover, UnitInspectionService $inspections)
    {
        $this->authorize('manage', $turnover);

        if (! $turnover->next_tenant_id) {
            return back()->withErrors(['inspection' => 'A new tenancy must be linked before a move-in inspection can be created.']);
        }

        $existing = $turnover->incomingMoveInInspection();

        if ($existing) {
            return redirect()->route('landlord.passport.inspections.show', $existing);
        }

        $inspection = $inspections->create($turnover->unit, UnitInspection::TYPE_MOVE_IN, auth()->user(), [
            'tenant_id' => $turnover->next_tenant_id,
            'inspection_date' => now()->toDateString(),
        ]);

        return redirect()
            ->route('landlord.passport.inspections.show', $inspection)
            ->with('success', 'Move-in inspection started for the incoming tenancy.');
    }
}

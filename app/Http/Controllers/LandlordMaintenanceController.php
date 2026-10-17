<?php

namespace App\Http\Controllers;

use App\Http\Requests\Maintenance\AssignMaintenanceRequest;
use App\Http\Requests\Maintenance\ChangeMaintenanceDeadlineRequest;
use App\Http\Requests\Maintenance\ReturnMaintenanceRequest;
use App\Http\Requests\Maintenance\StoreMaintenanceEvidenceRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceStatusRequest;
use App\Http\Requests\Maintenance\VerifyMaintenanceRequest;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestEvent;
use App\Models\MaintenanceRequestEvidence;
use App\Models\Property;
use App\Models\User;
use App\Services\MaintenanceAccountabilityService;
use App\Services\TurnoverService;
use Illuminate\Http\Request;

class LandlordMaintenanceController extends Controller
{
    public function __construct(protected MaintenanceAccountabilityService $accountability) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $base = MaintenanceRequest::whereIn('property_id', $propertyIds);

        $counts = [
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'awaiting_verification' => (clone $base)->awaitingVerification()->count(),
            'unassigned' => (clone $base)->whereNull('assigned_to')->unresolved()->count(),
        ];

        $query = (clone $base)->with(['tenant', 'property', 'unit', 'assignedTo']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->filled('scope')) {
            match ($request->scope) {
                'overdue' => $query->overdue(),
                'awaiting_verification' => $query->awaitingVerification(),
                'unassigned' => $query->whereNull('assigned_to')->unresolved(),
                'returned' => $query->whereNotNull('returned_at'),
                default => null,
            };
        }

        $requests = $query->latest()->paginate(15)->appends($request->query());

        $caretakers = $this->caretakersFor(auth()->user());

        return view('landlord.maintenance.index', compact('requests', 'caretakers', 'counts'));
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);

        $maintenance->load(['events.user', 'evidence.user', 'assignedTo', 'assignedBy', 'returnedBy', 'verifiedBy', 'tenant', 'property', 'unit', 'turnover']);

        $caretakers = $this->caretakersFor(auth()->user());

        return view('landlord.maintenance.show', compact('maintenance', 'caretakers'));
    }

    public function assign(AssignMaintenanceRequest $request, MaintenanceRequest $maintenance)
    {
        $caretaker = $request->filled('assigned_to')
            ? User::find($request->assigned_to)
            : null;

        $this->accountability->assign($maintenance, $caretaker, auth()->user(), $request->input('due_date'));

        app(TurnoverService::class)->syncForMaintenance($maintenance->fresh(), auth()->user());

        return back()->with('success', $caretaker
            ? 'Request assigned to caretaker successfully.'
            : 'Request returned to the unassigned pool.');
    }

    public function updateStatus(UpdateMaintenanceStatusRequest $request, MaintenanceRequest $maintenance)
    {
        $before = $maintenance->status;
        $data = ['status' => $request->status];

        if ($request->status === 'resolved') {
            $data['resolved_at'] = now();
            $data['resolution_notes'] = $request->resolution_notes;
        }

        $maintenance->update($data);

        if ($before !== $request->status) {
            $this->accountability->recordEvent(
                $maintenance,
                MaintenanceRequestEvent::EVENT_STATUS_CHANGED,
                auth()->user(),
                'Status changed from '.$before.' to '.$request->status.'.',
                ['from' => $before, 'to' => $request->status]
            );
        }

        app(TurnoverService::class)->syncForMaintenance($maintenance->fresh(), auth()->user());

        return back()->with('success', 'Status updated successfully.');
    }

    public function verify(VerifyMaintenanceRequest $request, MaintenanceRequest $maintenance)
    {
        $this->accountability->verify($maintenance, auth()->user(), $request->input('verification_notes'));

        app(TurnoverService::class)->syncForMaintenance($maintenance->fresh(), auth()->user());

        return back()->with('success', 'Work verified as complete.');
    }

    public function return(ReturnMaintenanceRequest $request, MaintenanceRequest $maintenance)
    {
        $this->accountability->returnForRework($maintenance, auth()->user(), $request->return_reason);

        app(TurnoverService::class)->syncForMaintenance($maintenance->fresh(), auth()->user());

        return back()->with('success', 'Task returned to the caretaker for further work.');
    }

    public function changeDeadline(ChangeMaintenanceDeadlineRequest $request, MaintenanceRequest $maintenance)
    {
        $this->accountability->changeDeadline($maintenance, $request->input('due_date'), auth()->user());

        return back()->with('success', 'Deadline updated.');
    }

    public function addEvidence(StoreMaintenanceEvidenceRequest $request, MaintenanceRequest $maintenance)
    {
        $this->accountability->addEvidence(
            $maintenance,
            auth()->user(),
            $request->file('evidence', []),
            MaintenanceRequestEvidence::TYPE_ISSUE
        );

        return back()->with('success', 'Evidence uploaded.');
    }

    protected function caretakersFor(User $landlord)
    {
        return User::where('role', 'caretaker')
            ->where('is_active', true)
            ->whereHas('caretakerProperties', fn ($q) => $q->where('properties.user_id', $landlord->id))
            ->orderBy('name')
            ->get();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Maintenance\StoreMaintenanceRequest;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestEvent;
use App\Models\Tenant;

class TenantMaintenanceController extends Controller
{
    public function index()
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();
        $requests = MaintenanceRequest::where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(15);

        return view('tenant.maintenance.index', compact('requests'));
    }

    public function create()
    {
        return view('tenant.maintenance.create');
    }

    public function store(StoreMaintenanceRequest $request)
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();
        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('maintenance', 'public');
        }

        MaintenanceRequest::create([
            'tenant_id' => $tenant->id,
            'property_id' => $tenant->property_id,
            'unit_id' => $tenant->unit_id,
            'title' => $request->title,
            'description' => $request->description,
            'category' => $request->category,
            'priority' => $request->priority,
            'photo' => $photoPath,
            'status' => 'pending',
        ]);

        return redirect()->route('tenant.maintenance.index')
            ->with('success', 'Maintenance request submitted successfully.');
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);

        $maintenance->load(['events.user', 'evidence.user', 'assignedTo', 'verifiedBy', 'property', 'unit']);

        // Tenants see the accountable progress of their own request, but not
        // internal caretaker-to-landlord requests (e.g. correction requests).
        $timeline = $maintenance->events
            ->whereIn('event', [
                MaintenanceRequestEvent::EVENT_ASSIGNED,
                MaintenanceRequestEvent::EVENT_REASSIGNED,
                MaintenanceRequestEvent::EVENT_UNASSIGNED,
                MaintenanceRequestEvent::EVENT_STARTED,
                MaintenanceRequestEvent::EVENT_DEADLINE_CHANGED,
                MaintenanceRequestEvent::EVENT_EVIDENCE_ADDED,
                MaintenanceRequestEvent::EVENT_COMPLETION_SUBMITTED,
                MaintenanceRequestEvent::EVENT_VERIFIED,
                MaintenanceRequestEvent::EVENT_RETURNED,
                MaintenanceRequestEvent::EVENT_ESCALATED,
            ])
            ->values();

        return view('tenant.maintenance.show', compact('maintenance', 'timeline'));
    }
}

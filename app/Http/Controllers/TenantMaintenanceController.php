<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;
use App\Models\Tenant;

class TenantMaintenanceController extends Controller
{
    public function index()
    {
        $tenant   = Tenant::where('user_id', auth()->id())->firstOrFail();
        $requests = MaintenanceRequest::where('tenant_id', $tenant->id)
                                       ->latest()
                                       ->get();

        return view('tenant.maintenance.index', compact('requests'));
    }

    public function create()
    {
        return view('tenant.maintenance.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'category'    => 'required|in:plumbing,electrical,security,cleaning,general',
            'priority'    => 'required|in:low,medium,high',
            'photo'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $tenant    = Tenant::where('user_id', auth()->id())->firstOrFail();
        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('maintenance', 'public');
        }

        MaintenanceRequest::create([
            'tenant_id'   => $tenant->id,
            'property_id' => $tenant->property_id,
            'unit_id'     => $tenant->unit_id,
            'title'       => $request->title,
            'description' => $request->description,
            'category'    => $request->category,
            'priority'    => $request->priority,
            'photo'       => $photoPath,
            'status'      => 'pending',
        ]);

        return redirect()->route('tenant.maintenance.index')
                         ->with('success', 'Maintenance request submitted successfully.');
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();
        abort_if($maintenance->tenant_id !== $tenant->id, 403);

        return view('tenant.maintenance.show', compact('maintenance'));
    }
}

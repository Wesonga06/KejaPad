<?php

namespace App\Http\Controllers;

use App\Http\Requests\Maintenance\AssignMaintenanceRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceStatusRequest;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;

class LandlordMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $query = MaintenanceRequest::whereIn('property_id', $propertyIds)
            ->with(['tenant', 'property', 'unit', 'assignedTo']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();
        $caretakers = User::where('role', 'caretaker')->where('is_active', true)->get();

        return view('landlord.maintenance.index', compact('requests', 'caretakers'));
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);

        $caretakers = User::where('role', 'caretaker')->where('is_active', true)->get();

        return view('landlord.maintenance.show', compact('maintenance', 'caretakers'));
    }

    public function assign(AssignMaintenanceRequest $request, MaintenanceRequest $maintenance)
    {
        $maintenance->update([
            'assigned_to' => $request->assigned_to,
            'status' => 'assigned',
        ]);

        return back()->with('success', 'Request assigned to caretaker successfully.');
    }

    public function updateStatus(UpdateMaintenanceStatusRequest $request, MaintenanceRequest $maintenance)
    {
        $data = ['status' => $request->status];

        if ($request->status === 'resolved') {
            $data['resolved_at'] = now();
            $data['resolution_notes'] = $request->resolution_notes;
        }

        $maintenance->update($data);

        return back()->with('success', 'Status updated successfully.');
    }
}

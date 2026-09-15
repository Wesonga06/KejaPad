<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\User;

class LandlordMaintenanceController extends Controller
{
    public function index(Request $request)
    {
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

        $requests   = $query->latest()->get();
        $caretakers = User::where('role', 'caretaker')->where('is_active', true)->get();

        return view('landlord.maintenance.index', compact('requests', 'caretakers'));
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
        abort_if(!$propertyIds->contains($maintenance->property_id), 403);

        $caretakers = User::where('role', 'caretaker')->where('is_active', true)->get();

        return view('landlord.maintenance.show', compact('maintenance', 'caretakers'));
    }

    public function assign(Request $request, MaintenanceRequest $maintenance)
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
        abort_if(!$propertyIds->contains($maintenance->property_id), 403);

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $maintenance->update([
            'assigned_to' => $request->assigned_to,
            'status'      => 'assigned',
        ]);

        return back()->with('success', 'Request assigned to caretaker successfully.');
    }

    public function updateStatus(Request $request, MaintenanceRequest $maintenance)
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
        abort_if(!$propertyIds->contains($maintenance->property_id), 403);

        $request->validate([
            'status' => 'required|in:pending,assigned,in_progress,resolved,closed',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'resolved') {
            $data['resolved_at']       = now();
            $data['resolution_notes']  = $request->resolution_notes;
        }

        $maintenance->update($data);

        return back()->with('success', 'Status updated successfully.');
    }
}

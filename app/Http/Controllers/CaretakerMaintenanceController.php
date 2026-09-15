<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;

class CaretakerMaintenanceController extends Controller
{
    public function index()
    {
        $requests = MaintenanceRequest::where('assigned_to', auth()->id())
                                       ->with(['tenant', 'property', 'unit'])
                                       ->latest()
                                       ->get();

        $pendingCount    = $requests->where('status', 'pending')->count();
        $inProgressCount = $requests->where('status', 'in_progress')->count();
        $resolvedCount   = $requests->where('status', 'resolved')->count();

        return view('caretaker.maintenance.index', compact(
            'requests',
            'pendingCount',
            'inProgressCount',
            'resolvedCount'
        ));
    }

    public function show(MaintenanceRequest $maintenance)
    {
        abort_if($maintenance->assigned_to !== auth()->id(), 403);

        return view('caretaker.maintenance.show', compact('maintenance'));
    }

    public function updateStatus(Request $request, MaintenanceRequest $maintenance)
    {
        abort_if($maintenance->assigned_to !== auth()->id(), 403);

        $request->validate([
            'status'           => 'required|in:in_progress,resolved',
            'resolution_notes' => 'nullable|string',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'resolved') {
            $data['resolved_at']      = now();
            $data['resolution_notes'] = $request->resolution_notes;
        }

        $maintenance->update($data);

        return back()->with('success', 'Status updated successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Maintenance\UpdateCaretakerStatusRequest;
use App\Models\MaintenanceRequest;

class CaretakerMaintenanceController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $base = MaintenanceRequest::visibleToCaretaker(auth()->user());

        $totalRequests = (clone $base)->count();
        $pendingCount = (clone $base)->where('status', 'pending')->count();
        $inProgressCount = (clone $base)->where('status', 'in_progress')->count();
        $resolvedCount = (clone $base)->where('status', 'resolved')->count();

        $requests = $base->with(['tenant', 'property', 'unit'])
            ->latest()
            ->paginate(15);

        return view('caretaker.maintenance.index', compact(
            'requests',
            'totalRequests',
            'pendingCount',
            'inProgressCount',
            'resolvedCount'
        ));
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);

        return view('caretaker.maintenance.show', compact('maintenance'));
    }

    public function updateStatus(UpdateCaretakerStatusRequest $request, MaintenanceRequest $maintenance)
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

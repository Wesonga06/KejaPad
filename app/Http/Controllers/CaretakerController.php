<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\MoveOutInspection;

class CaretakerController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $pendingCount = MaintenanceRequest::visibleToCaretaker($user)
            ->where('status', 'pending')->count();
        $inProgressCount = MaintenanceRequest::visibleToCaretaker($user)
            ->where('status', 'in_progress')->count();
        $resolvedCount = MaintenanceRequest::visibleToCaretaker($user)
            ->where('status', 'resolved')->count();
        $totalCount = MaintenanceRequest::visibleToCaretaker($user)->count();

        $inspectionBase = MoveOutInspection::where('caretaker_id', $user->id);

        $pendingInspections = (clone $inspectionBase)
            ->whereIn('status', [
                MoveOutInspection::STATUS_ASSIGNED,
                MoveOutInspection::STATUS_IN_PROGRESS,
            ])->count();

        $upcomingInspections = (clone $inspectionBase)
            ->whereIn('status', [
                MoveOutInspection::STATUS_ASSIGNED,
                MoveOutInspection::STATUS_IN_PROGRESS,
            ])
            ->with(['moveOutRequest.tenant', 'moveOutRequest.unit', 'moveOutRequest.property'])
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        return view('caretaker.dashboard', compact(
            'pendingCount',
            'inProgressCount',
            'resolvedCount',
            'totalCount',
            'pendingInspections',
            'upcomingInspections'
        ));
    }
}

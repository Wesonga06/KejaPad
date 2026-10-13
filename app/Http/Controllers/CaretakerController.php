<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;

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

        return view('caretaker.dashboard', compact(
            'pendingCount',
            'inProgressCount',
            'resolvedCount',
            'totalCount'
        ));
    }
}

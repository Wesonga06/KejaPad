<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceRequest;

class CaretakerController extends Controller
{
    public function dashboard()
    {
        $pendingCount    = MaintenanceRequest::where('assigned_to', auth()->id())
                           ->where('status', 'pending')->count();
        $inProgressCount = MaintenanceRequest::where('assigned_to', auth()->id())
                           ->where('status', 'in_progress')->count();
        $resolvedCount   = MaintenanceRequest::where('assigned_to', auth()->id())
                           ->where('status', 'resolved')->count();
        $totalCount      = MaintenanceRequest::where('assigned_to', auth()->id())->count();

        return view('caretaker.dashboard', compact(
            'pendingCount',
            'inProgressCount',
            'resolvedCount',
            'totalCount'
        ));
    }
}

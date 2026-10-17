<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Services\CaretakerPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CaretakerHistoryController extends Controller
{
    public function __construct(protected CaretakerPerformanceService $performance) {}

    // A caretaker's own work history and evidence-based summary. Records persist
    // even after a caretaker leaves a property, because they are keyed to the
    // task's assigned caretaker, not to the current property assignment.
    public function index(Request $request)
    {
        $user = auth()->user();

        $from = $request->input('from');
        $to = $request->input('to');

        $summary = $this->performance->summary($user, [
            'from' => $from,
            'to' => $to,
        ]);

        $query = MaintenanceRequest::where('assigned_to', $user->id)
            ->with(['property', 'unit', 'tenant'])
            ->latest();

        if ($from) {
            $query->whereRaw('COALESCE(assigned_at, created_at) >= ?', [Carbon::parse($from)->startOfDay()->toDateTimeString()]);
        }

        if ($to) {
            $query->whereRaw('COALESCE(assigned_at, created_at) <= ?', [Carbon::parse($to)->endOfDay()->toDateTimeString()]);
        }

        $tasks = $query->paginate(15)->appends($request->query());

        return view('caretaker.history', compact('summary', 'tasks'));
    }
}

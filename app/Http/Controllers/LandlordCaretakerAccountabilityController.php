<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\User;
use App\Services\CaretakerPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LandlordCaretakerAccountabilityController extends Controller
{
    public function __construct(protected CaretakerPerformanceService $performance) {}

    public function index(Request $request)
    {
        $landlord = auth()->user();
        $propertyIds = Property::where('user_id', $landlord->id)->pluck('id');

        $from = $request->input('from');
        $to = $request->input('to');

        $caretakers = $this->caretakersFor($landlord, $propertyIds);

        $summaries = $caretakers->map(fn (User $caretaker) => $this->performance->summary($caretaker, [
            'from' => $from,
            'to' => $to,
            'property_ids' => $propertyIds->all(),
        ]));

        return view('landlord.caretakers.index', compact('summaries', 'from', 'to'));
    }

    public function show(Request $request, User $caretaker)
    {
        abort_unless($caretaker->isCaretaker(), 404);

        $landlord = auth()->user();
        $propertyIds = Property::where('user_id', $landlord->id)->pluck('id');

        $attached = $caretaker->caretakerProperties()->whereIn('properties.id', $propertyIds)->exists();
        $hasTasks = MaintenanceRequest::where('assigned_to', $caretaker->id)
            ->whereIn('property_id', $propertyIds)
            ->exists();

        abort_unless($attached || $hasTasks, 404);

        $from = $request->input('from');
        $to = $request->input('to');

        $summary = $this->performance->summary($caretaker, [
            'from' => $from,
            'to' => $to,
            'property_ids' => $propertyIds->all(),
        ]);

        $query = MaintenanceRequest::where('assigned_to', $caretaker->id)
            ->whereIn('property_id', $propertyIds)
            ->with(['property', 'unit', 'tenant'])
            ->latest();

        if ($from) {
            $query->whereRaw('COALESCE(assigned_at, created_at) >= ?', [Carbon::parse($from)->startOfDay()->toDateTimeString()]);
        }

        if ($to) {
            $query->whereRaw('COALESCE(assigned_at, created_at) <= ?', [Carbon::parse($to)->endOfDay()->toDateTimeString()]);
        }

        $tasks = $query->paginate(15)->appends($request->query());

        return view('landlord.caretakers.show', compact('summary', 'tasks', 'caretaker', 'from', 'to'));
    }

    protected function caretakersFor(User $landlord, $propertyIds)
    {
        return User::where('role', 'caretaker')
            ->where(function ($query) use ($propertyIds) {
                $query->whereHas('caretakerProperties', fn ($q) => $q->whereIn('properties.id', $propertyIds))
                    ->orWhereHas('maintenanceTasks', fn ($q) => $q->whereIn('property_id', $propertyIds));
            })
            ->orderBy('name')
            ->get();
    }
}

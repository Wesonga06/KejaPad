<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Tenant;
use App\Services\RentStatusService;
use Illuminate\Http\Request;

class ArrearsController extends Controller
{
    // Landlord overview of rent arrears, colour-coded by derived status.
    public function index(Request $request, RentStatusService $status)
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $query = Tenant::whereIn('property_id', $propertyIds)
            ->where('is_active', true)
            ->with(['property', 'unit']);

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        $rows = $query->get()
            ->map(function (Tenant $tenant) use ($status) {
                $summary = $status->summary($tenant);

                return (object) [
                    'tenant' => $tenant,
                    'summary' => $summary,
                ];
            })
            ->filter(fn ($row) => $row->summary['outstanding'] > 0 || $row->summary['arrears'] > 0)
            ->sortByDesc(fn ($row) => $row->summary['max_overdue_days'])
            ->values();

        $counts = [
            'green' => $rows->where('summary.status_key', 'green')->count(),
            'red' => $rows->where('summary.status_key', 'red')->count(),
            'orange' => $rows->where('summary.status_key', 'orange')->count(),
            'yellow' => $rows->where('summary.status_key', 'yellow')->count(),
        ];

        $totalArrears = round($rows->sum(fn ($row) => $row->summary['arrears']), 2);
        $properties = Property::where('user_id', auth()->id())->get();

        return view('landlord.arrears.index', compact(
            'rows', 'counts', 'totalArrears', 'properties'
        ));
    }
}

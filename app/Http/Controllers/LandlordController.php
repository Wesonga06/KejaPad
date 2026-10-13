<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Unit;
use App\Services\RentStatusService;

class LandlordController extends Controller
{
    public function dashboard(RentStatusService $status)
    {
        $properties = Property::where('user_id', auth()->id())->get();
        $propertyIds = $properties->pluck('id')->toArray();

        $totalProperties = $properties->count();
        $totalUnits = Unit::whereIn('property_id', $propertyIds)->count();
        $occupiedUnits = Unit::whereIn('property_id', $propertyIds)
            ->where('status', 'occupied')->count();
        $vacantUnits = Unit::whereIn('property_id', $propertyIds)
            ->where('status', 'vacant')->count();
        $totalTenants = Tenant::whereIn('property_id', $propertyIds)
            ->where('is_active', true)->count();

        // Rent stats for current month
        $currentMonth = date('n');
        $currentYear = date('Y');

        $rentRecords = RentRecord::whereIn('property_id', $propertyIds)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->get();

        $totalExpected = $rentRecords->sum('amount_due');
        $totalCollected = $rentRecords->sum('amount_paid');
        $totalOutstanding = $totalExpected - $totalCollected;

        // Arrears across all active tenants (derived from the payment ledger)
        $activeTenants = Tenant::whereIn('property_id', $propertyIds)
            ->where('is_active', true)
            ->get();

        $arrearsTotal = 0.0;
        $statusCounts = ['green' => 0, 'red' => 0, 'orange' => 0, 'yellow' => 0];

        foreach ($activeTenants as $tenant) {
            $summary = $status->summary($tenant);
            $arrearsTotal += $summary['arrears'];
            $statusCounts[$summary['status_key']]++;
        }

        $arrearsTotal = round($arrearsTotal, 2);

        return view('landlord.dashboard', compact(
            'totalProperties',
            'totalUnits',
            'occupiedUnits',
            'vacantUnits',
            'totalTenants',
            'totalExpected',
            'totalCollected',
            'totalOutstanding',
            'arrearsTotal',
            'statusCounts'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyAlert;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Turnover;
use App\Models\Unit;
use App\Services\CaretakerPerformanceService;
use App\Services\RentStatusService;

class LandlordController extends Controller
{
    // Status page shown to landlords who cannot yet access landlord features.
    public function pending()
    {
        $user = auth()->user();

        if ($user->canAccessLandlordFeatures()) {
            return redirect()->route('landlord.dashboard');
        }

        return view('landlord.pending', ['user' => $user]);
    }

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

        $occupancyRate = $totalUnits > 0
            ? (int) round(($occupiedUnits / $totalUnits) * 100)
            : 0;

        $collectionRate = $totalExpected > 0
            ? (int) round(($totalCollected / $totalExpected) * 100)
            : 0;

        $recentTenants = Tenant::whereIn('property_id', $propertyIds)
            ->where('is_active', true)
            ->with(['property', 'unit'])
            ->latest()
            ->take(5)
            ->get();

        $recentPayments = Payment::whereHas('tenant', function ($q) use ($propertyIds) {
            $q->whereIn('property_id', $propertyIds);
        })
            ->with(['tenant.property'])
            ->latest()
            ->take(5)
            ->get();

        // Turnover summary: units moving between tenancies.
        $turnoverBase = Turnover::whereIn('property_id', $propertyIds);
        $openTurnover = fn () => (clone $turnoverBase)->whereNotIn('status', [
            Turnover::STATUS_CANCELLED,
            Turnover::STATUS_OCCUPIED,
        ]);

        $approachingDays = (int) config('turnover.approaching_days', 7);

        $turnoverSummary = [
            'in_progress' => $openTurnover()->count(),
            'tasks_overdue' => MaintenanceRequest::whereIn('property_id', $propertyIds)
                ->whereNotNull('turnover_id')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                ->count(),
            'inspections_awaiting' => (clone $turnoverBase)->where('status', Turnover::STATUS_INSPECTION_PENDING)->count(),
            'repairs_awaiting_verification' => (clone $turnoverBase)->where('status', Turnover::STATUS_AWAITING_VERIFICATION)->count(),
            'ready' => (clone $turnoverBase)->where('status', Turnover::STATUS_READY_FOR_LETTING)->count(),
            'approaching' => $openTurnover()
                ->whereHas('moveOutRequest', fn ($q) => $q->whereDate('proposed_move_out_date', '<=', now()->addDays($approachingDays)->toDateString()))
                ->count(),
        ];

        $activeTurnovers = $openTurnover()
            ->with(['property', 'unit', 'outgoingTenant'])
            ->latest('last_activity_at')
            ->take(5)
            ->get();

        // Early-warning alerts: derived signals surfaced by the alert engine.
        $alertBase = PropertyAlert::whereIn('property_id', $propertyIds);

        $alertSummary = [
            'open' => (clone $alertBase)->unresolved()->count(),
            'critical' => (clone $alertBase)->unresolved()->where('severity', PropertyAlert::SEVERITY_CRITICAL)->count(),
            'warning' => (clone $alertBase)->unresolved()->where('severity', PropertyAlert::SEVERITY_WARNING)->count(),
        ];

        $recentAlerts = (clone $alertBase)
            ->unresolved()
            ->with(['property', 'unit'])
            ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
            ->orderByDesc('last_seen_at')
            ->take(5)
            ->get();

        // Caretaker accountability: neutral, evidence-based operational signals.
        $accountability = app(CaretakerPerformanceService::class)
            ->landlordOverview(auth()->user());

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
            'statusCounts',
            'occupancyRate',
            'collectionRate',
            'recentTenants',
            'recentPayments',
            'turnoverSummary',
            'activeTurnovers',
            'alertSummary',
            'recentAlerts',
            'accountability'
        ));
    }
}

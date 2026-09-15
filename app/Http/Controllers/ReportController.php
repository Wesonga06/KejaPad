<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Unit;
use App\Models\Tenant;
use App\Models\RentRecord;
use App\Models\MaintenanceRequest;
use App\Exports\RentCollectionExport;
use App\Exports\OutstandingBalancesExport;
use App\Exports\OccupancyExport;
use App\Exports\MaintenanceExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    // Main Reports Page
    public function index()
    {
        $properties = Property::where('user_id', auth()->id())->get();
        return view('landlord.reports.index', compact('properties'));
    }

    // Rent Collection Report
    public function rentCollection(Request $request)
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $query = RentRecord::whereIn('property_id', $propertyIds)
                            ->with(['tenant', 'property', 'unit']);

        if ($request->filled('month'))       $query->where('month', $request->month);
        if ($request->filled('year'))        $query->where('year', $request->year);
        if ($request->filled('property_id')) $query->where('property_id', $request->property_id);
        if ($request->filled('status'))      $query->where('status', $request->status);

        $records          = $query->latest()->get();
        $totalExpected    = $records->sum('amount_due');
        $totalCollected   = $records->sum('amount_paid');
        $totalOutstanding = $totalExpected - $totalCollected;
        $properties       = Property::where('user_id', auth()->id())->get();

        return view('landlord.reports.rent-collection', compact(
            'records', 'totalExpected', 'totalCollected',
            'totalOutstanding', 'properties'
        ));
    }

    // Outstanding Balances Report
    public function outstandingBalances()
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $records = RentRecord::whereIn('property_id', $propertyIds)
                              ->whereIn('status', ['pending', 'partial', 'overdue'])
                              ->with(['tenant', 'property', 'unit'])
                              ->latest()
                              ->get();

        $totalOutstanding = $records->sum(fn($r) => $r->balance());

        return view('landlord.reports.outstanding', compact('records', 'totalOutstanding'));
    }

    // Occupancy Report
    public function occupancy()
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $units        = Unit::whereIn('property_id', $propertyIds)
                             ->with(['property', 'tenant'])
                             ->get();

        $totalUnits   = $units->count();
        $occupied     = $units->where('status', 'occupied')->count();
        $vacant       = $units->where('status', 'vacant')->count();
        $reserved     = $units->where('status', 'reserved')->count();
        $occupancyRate = $totalUnits > 0
                         ? round(($occupied / $totalUnits) * 100, 1)
                         : 0;

        return view('landlord.reports.occupancy', compact(
            'units', 'totalUnits', 'occupied',
            'vacant', 'reserved', 'occupancyRate'
        ));
    }

    // Maintenance Report
    public function maintenance()
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $requests = MaintenanceRequest::whereIn('property_id', $propertyIds)
                                       ->with(['tenant', 'property', 'unit', 'assignedTo'])
                                       ->latest()
                                       ->get();

        $pending    = $requests->where('status', 'pending')->count();
        $inProgress = $requests->where('status', 'in_progress')->count();
        $resolved   = $requests->where('status', 'resolved')->count();
        $closed     = $requests->where('status', 'closed')->count();

        return view('landlord.reports.maintenance', compact(
            'requests', 'pending', 'inProgress', 'resolved', 'closed'
        ));
    }

    // Export Excel
    public function exportExcel(Request $request, $type)
    {
        $landlordId = auth()->id();
        $month      = $request->month;
        $year       = $request->year;
        $propertyId = $request->property_id;

        return match($type) {
            'rent-collection'   => Excel::download(
                new RentCollectionExport($month, $year, $propertyId, $landlordId),
                'rent-collection-' . now()->format('Y-m-d') . '.xlsx'
            ),
            'outstanding'       => Excel::download(
                new OutstandingBalancesExport($landlordId),
                'outstanding-balances-' . now()->format('Y-m-d') . '.xlsx'
            ),
            'occupancy'         => Excel::download(
                new OccupancyExport($landlordId),
                'occupancy-report-' . now()->format('Y-m-d') . '.xlsx'
            ),
            'maintenance'       => Excel::download(
                new MaintenanceExport($landlordId),
                'maintenance-report-' . now()->format('Y-m-d') . '.xlsx'
            ),
            default             => back()->with('error', 'Invalid report type.')
        };
    }

    // Export PDF
    public function exportPdf(Request $request, $type)
    {
        $landlordId  = auth()->id();
        $propertyIds = Property::where('user_id', $landlordId)->pluck('id');

        switch ($type) {
            case 'rent-collection':
                $query = RentRecord::whereIn('property_id', $propertyIds)
                                    ->with(['tenant', 'property', 'unit']);
                if ($request->filled('month'))       $query->where('month', $request->month);
                if ($request->filled('year'))        $query->where('year', $request->year);
                if ($request->filled('property_id')) $query->where('property_id', $request->property_id);
                $records        = $query->latest()->get();
                $totalExpected  = $records->sum('amount_due');
                $totalCollected = $records->sum('amount_paid');
                $pdf = Pdf::loadView('landlord.reports.pdf.rent-collection', compact(
                    'records', 'totalExpected', 'totalCollected'
                ))->setPaper('a4', 'landscape');
                return $pdf->download('rent-collection-' . now()->format('Y-m-d') . '.pdf');

            case 'outstanding':
                $records = RentRecord::whereIn('property_id', $propertyIds)
                                      ->whereIn('status', ['pending', 'partial', 'overdue'])
                                      ->with(['tenant', 'property', 'unit'])
                                      ->get();
                $totalOutstanding = $records->sum(fn($r) => $r->balance());
                $pdf = Pdf::loadView('landlord.reports.pdf.outstanding', compact(
                    'records', 'totalOutstanding'
                ))->setPaper('a4', 'landscape');
                return $pdf->download('outstanding-balances-' . now()->format('Y-m-d') . '.pdf');

            case 'occupancy':
                $units = Unit::whereIn('property_id', $propertyIds)
                              ->with(['property', 'tenant'])
                              ->get();
                $pdf = Pdf::loadView('landlord.reports.pdf.occupancy', compact('units'))
                           ->setPaper('a4', 'landscape');
                return $pdf->download('occupancy-report-' . now()->format('Y-m-d') . '.pdf');

            case 'maintenance':
                $requests = MaintenanceRequest::whereIn('property_id', $propertyIds)
                                               ->with(['tenant', 'property', 'unit', 'assignedTo'])
                                               ->get();
                $pdf = Pdf::loadView('landlord.reports.pdf.maintenance', compact('requests'))
                           ->setPaper('a4', 'landscape');
                return $pdf->download('maintenance-report-' . now()->format('Y-m-d') . '.pdf');

            default:
                return back()->with('error', 'Invalid report type.');
        }
    }
}

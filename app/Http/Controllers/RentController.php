<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Models\Property;

class RentController extends Controller
{
    // List all rent records for this landlord
    public function index(Request $request)
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $query = RentRecord::whereIn('property_id', $propertyIds)
                            ->with(['tenant', 'property', 'unit']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        $rentRecords = $query->latest()->get();

        // Summary stats
        $totalExpected    = $rentRecords->sum('amount_due');
        $totalCollected   = $rentRecords->sum('amount_paid');
        $totalOutstanding = $totalExpected - $totalCollected;
        $totalOverdue     = $rentRecords->where('status', 'overdue')->sum('amount_due');

        $properties = Property::where('user_id', auth()->id())->get();

        return view('landlord.rent.index', compact(
            'rentRecords',
            'totalExpected',
            'totalCollected',
            'totalOutstanding',
            'totalOverdue',
            'properties'
        ));
    }

    // Show create form
    public function create()
    {
        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
        $tenants     = Tenant::whereIn('property_id', $propertyIds)
                              ->where('is_active', true)
                              ->with(['property', 'unit'])
                              ->get();

        return view('landlord.rent.create', compact('tenants'));
    }

    // Store new rent record
    public function store(Request $request)
    {
        $request->validate([
            'tenant_id'  => 'required|exists:tenants,id',
            'amount_due' => 'required|numeric|min:1',
            'due_date'   => 'required|date',
            'month'      => 'required|integer|between:1,12',
            'year'       => 'required|integer|min:2020',
            'notes'      => 'nullable|string',
        ]);

        $tenant = Tenant::findOrFail($request->tenant_id);

        // Make sure landlord owns this tenant's property
        $property = $tenant->property;
        abort_if($property->user_id !== auth()->id(), 403);

        // Check if rent record already exists for this tenant/month/year
        $exists = RentRecord::where('tenant_id', $request->tenant_id)
                             ->where('month', $request->month)
                             ->where('year', $request->year)
                             ->exists();

        if ($exists) {
            return back()->withErrors([
                'month' => 'A rent record for this tenant already exists for the selected month and year.'
            ])->withInput();
        }

        RentRecord::create([
            'tenant_id'   => $request->tenant_id,
            'property_id' => $tenant->property_id,
            'unit_id'     => $tenant->unit_id,
            'amount_due'  => $request->amount_due,
            'amount_paid' => 0,
            'due_date'    => $request->due_date,
            'status'      => 'pending',
            'month'       => $request->month,
            'year'        => $request->year,
            'notes'       => $request->notes,
        ]);

        return redirect()->route('landlord.rent.index')
                         ->with('success', 'Rent record created successfully.');
    }

    // Show rent record detail
    public function show(RentRecord $rent)
    {
        abort_if($rent->property->user_id !== auth()->id(), 403);
        return view('landlord.rent.show', compact('rent'));
    }

    // Record a payment
    public function recordPayment(Request $request, RentRecord $rent)
    {
        abort_if($rent->property->user_id !== auth()->id(), 403);

        $request->validate([
            'amount_paid'      => 'required|numeric|min:1',
            'payment_date'     => 'required|date',
            'transaction_code' => 'nullable|string|max:50',
        ]);

        $totalPaid = $rent->amount_paid + $request->amount_paid;

        // Determine status
        if ($totalPaid >= $rent->amount_due) {
            $status    = 'paid';
            $totalPaid = $rent->amount_due; // cap at amount due
        } else {
            $status = 'partial';
        }

        $rent->update([
            'amount_paid'      => $totalPaid,
            'payment_date'     => $request->payment_date,
            'transaction_code' => $request->transaction_code,
            'status'           => $status,
        ]);

        return back()->with('success', 'Payment recorded successfully.');
    }

    // Mark overdue
    public function markOverdue(RentRecord $rent)
    {
        abort_if($rent->property->user_id !== auth()->id(), 403);

        $rent->update(['status' => 'overdue']);

        return back()->with('success', 'Rent record marked as overdue.');
    }

    // Generate rent records for all active tenants in bulk
    public function generateBulk(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2020',
            'day'   => 'required|integer|between:1,28',
        ]);

        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');
        $tenants     = Tenant::whereIn('property_id', $propertyIds)
                              ->where('is_active', true)
                              ->get();

        $created = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            $exists = RentRecord::where('tenant_id', $tenant->id)
                                 ->where('month', $request->month)
                                 ->where('year', $request->year)
                                 ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $dueDate = $request->year . '-' .
                       str_pad($request->month, 2, '0', STR_PAD_LEFT) . '-' .
                       str_pad($request->day, 2, '0', STR_PAD_LEFT);

            RentRecord::create([
                'tenant_id'   => $tenant->id,
                'property_id' => $tenant->property_id,
                'unit_id'     => $tenant->unit_id,
                'amount_due'  => $tenant->unit->rent_amount,
                'amount_paid' => 0,
                'due_date'    => $dueDate,
                'status'      => 'pending',
                'month'       => $request->month,
                'year'        => $request->year,
            ]);

            $created++;
        }

        return back()->with('success', "Rent records generated: {$created} created, {$skipped} skipped (already exist).");
    }
}

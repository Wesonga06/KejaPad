<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LandlordTenantController extends Controller
{
    // List all tenants for this landlord
    public function index()
    {
        $tenants = Tenant::whereHas('property', function ($q) {
                        $q->where('user_id', auth()->id());
                    })
                    ->with(['property', 'unit'])
                    ->latest()
                    ->get();

        return view('landlord.tenants.index', compact('tenants'));
    }

    // Show create form
    public function create()
{
    $properties = Property::where('user_id', auth()->id())->get();

    return view('landlord.tenants.create', compact('properties'));
}

    // Get vacant units for a property (AJAX)
    public function getUnits(Property $property)
    {
        abort_if($property->user_id !== auth()->id(), 403);

        $units = $property->units()
                          ->where('status', 'vacant')
                          ->get(['id', 'unit_number', 'rent_amount']);

        return response()->json($units);
    }

    // Store new tenant
    public function store(Request $request)
    {
        $request->validate([
            'full_name'               => 'required|string|max:255',
            'phone'                   => 'required|string|max:20',
            'email'                   => 'nullable|email|max:255',
            'national_id'             => 'nullable|string|max:20',
            'emergency_contact_name'  => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'property_id'             => 'required|exists:properties,id',
            'unit_id'                 => 'required|exists:units,id',
            'move_in_date'            => 'required|date',
        ]);

        // Make sure landlord owns this property
        $property = Property::findOrFail($request->property_id);
        abort_if($property->user_id !== auth()->id(), 403);

        // Make sure unit is vacant
        $unit = Unit::findOrFail($request->unit_id);
        if ($unit->status !== 'vacant') {
            return back()->withErrors(['unit_id' => 'This unit is not vacant.'])
                         ->withInput();
        }

        $userId = null;

        // Create login account if requested
        if ($request->filled('create_account') && $request->filled('email')) {
            $request->validate([
                'password' => 'required|min:8|confirmed',
            ]);

            if (User::where('email', $request->email)->exists()) {
                return back()->withErrors(['email' => 'This email is already registered.'])
                             ->withInput();
            }

            $user = User::create([
                'name'      => $request->full_name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'role'      => 'tenant',
                'is_active' => true,
            ]);

            $userId = $user->id;
        }

        // Create tenant record
        Tenant::create([
            'user_id'                 => $userId,
            'property_id'             => $request->property_id,
            'unit_id'                 => $request->unit_id,
            'full_name'               => $request->full_name,
            'phone'                   => $request->phone,
            'email'                   => $request->email,
            'national_id'             => $request->national_id,
            'emergency_contact_name'  => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'move_in_date'            => $request->move_in_date,
            'is_active'               => true,
        ]);

        // Mark unit as occupied
        $unit->update(['status' => 'occupied']);

        return redirect()->route('landlord.tenants.index')
                         ->with('success', 'Tenant added successfully.');
    }

    // Show edit form
    public function edit(Tenant $tenant)
    {
        $property = $tenant->property;
        abort_if($property->user_id !== auth()->id(), 403);

        $properties = Property::where('user_id', auth()->id())
                               ->where('status', 'active')
                               ->get();

        $units = $property->units()
                           ->whereIn('status', ['vacant', 'occupied'])
                           ->get();

        return view('landlord.tenants.edit', compact('tenant', 'properties', 'units'));
    }

    // Update tenant
    public function update(Request $request, Tenant $tenant)
    {
        $property = $tenant->property;
        abort_if($property->user_id !== auth()->id(), 403);

        $request->validate([
            'full_name'               => 'required|string|max:255',
            'phone'                   => 'required|string|max:20',
            'email'                   => 'nullable|email|max:255',
            'national_id'             => 'nullable|string|max:20',
            'emergency_contact_name'  => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'move_in_date'            => 'required|date',
            'move_out_date'           => 'nullable|date|after:move_in_date',
        ]);

        $tenant->update([
            'full_name'               => $request->full_name,
            'phone'                   => $request->phone,
            'email'                   => $request->email,
            'national_id'             => $request->national_id,
            'emergency_contact_name'  => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'move_in_date'            => $request->move_in_date,
            'move_out_date'           => $request->move_out_date,
        ]);

        return redirect()->route('landlord.tenants.index')
                         ->with('success', 'Tenant updated successfully.');
    }

    // Deactivate tenant
    public function deactivate(Tenant $tenant)
    {
        $property = $tenant->property;
        abort_if($property->user_id !== auth()->id(), 403);

        $tenant->update([
            'is_active'     => false,
            'move_out_date' => now()->toDateString(),
        ]);

        // Free up the unit
        $tenant->unit->update(['status' => 'vacant']);

        // Deactivate user account if linked
        if ($tenant->user_id) {
            $tenant->user->update(['is_active' => false]);
        }

        return back()->with('success', 'Tenant deactivated and unit marked as vacant.');
    }

    // Show tenant details
    public function show(Tenant $tenant)
    {
        $property = $tenant->property;
        abort_if($property->user_id !== auth()->id(), 403);

        return view('landlord.tenants.show', compact('tenant'));
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Unit;
use App\Models\Property;

class UnitController extends Controller
{
    // List all units for a property
    public function index(Property $property)
    {
        abort_if($property->user_id !== auth()->id(), 403);
        $units = $property->units()->latest()->get();
        return view('landlord.units.index', compact('property', 'units'));
    }

    // Show create form
    public function create(Property $property)
    {
        abort_if($property->user_id !== auth()->id(), 403);
        return view('landlord.units.create', compact('property'));
    }

    // Store new unit
    public function store(Request $request, Property $property)
    {
        abort_if($property->user_id !== auth()->id(), 403);

        $request->validate([
            'unit_number' => 'required|string|max:50',
            'rent_amount' => 'required|numeric|min:0',
            'status'      => 'required|in:vacant,occupied,reserved',
            'description' => 'nullable|string',
        ]);

        // Check for duplicate unit number in same property
        $exists = $property->units()->where('unit_number', $request->unit_number)->exists();
        if ($exists) {
            return back()->withErrors(['unit_number' => 'This unit number already exists in this property.'])->withInput();
        }

        $property->units()->create([
            'unit_number' => $request->unit_number,
            'rent_amount' => $request->rent_amount,
            'status'      => $request->status,
            'description' => $request->description,
        ]);

        return redirect()->route('landlord.units.index', $property)
                         ->with('success', 'Unit added successfully.');
    }

    // Show edit form
    public function edit(Property $property, Unit $unit)
    {
        abort_if($property->user_id !== auth()->id(), 403);
        return view('landlord.units.edit', compact('property', 'unit'));
    }

    // Update unit
    public function update(Request $request, Property $property, Unit $unit)
    {
        abort_if($property->user_id !== auth()->id(), 403);

        $request->validate([
            'unit_number' => 'required|string|max:50',
            'rent_amount' => 'required|numeric|min:0',
            'status'      => 'required|in:vacant,occupied,reserved',
            'description' => 'nullable|string',
        ]);

        // Check for duplicate unit number excluding current unit
        $exists = $property->units()
                            ->where('unit_number', $request->unit_number)
                            ->where('id', '!=', $unit->id)
                            ->exists();
        if ($exists) {
            return back()->withErrors(['unit_number' => 'This unit number already exists in this property.'])->withInput();
        }

        $unit->update([
            'unit_number' => $request->unit_number,
            'rent_amount' => $request->rent_amount,
            'status'      => $request->status,
            'description' => $request->description,
        ]);

        return redirect()->route('landlord.units.index', $property)
                         ->with('success', 'Unit updated successfully.');
    }

    // Delete unit
    public function destroy(Property $property, Unit $unit)
    {
        abort_if($property->user_id !== auth()->id(), 403);
        $unit->delete();
        return back()->with('success', 'Unit deleted successfully.');
    }
}

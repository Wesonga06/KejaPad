<?php

namespace App\Http\Controllers;

use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Models\Property;
use App\Models\Unit;

class UnitController extends Controller
{
    // List all units for a property
    public function index(Property $property)
    {
        $this->authorize('viewAny', [Unit::class, $property]);

        $base = $property->units();

        $totalUnits = (clone $base)->count();
        $vacantCount = (clone $base)->where('status', 'vacant')->count();
        $occupiedCount = (clone $base)->where('status', 'occupied')->count();
        $reservedCount = (clone $base)->where('status', 'reserved')->count();

        $units = $base->latest()->paginate(12);

        return view('landlord.units.index', compact(
            'property',
            'units',
            'totalUnits',
            'vacantCount',
            'occupiedCount',
            'reservedCount'
        ));
    }

    // Show create form
    public function create(Property $property)
    {
        $this->authorize('create', [Unit::class, $property]);

        return view('landlord.units.create', compact('property'));
    }

    // Store new unit
    public function store(StoreUnitRequest $request, Property $property)
    {
        // Check for duplicate unit number in same property
        $exists = $property->units()->where('unit_number', $request->unit_number)->exists();
        if ($exists) {
            return back()->withErrors(['unit_number' => 'This unit number already exists in this property.'])->withInput();
        }

        $property->units()->create([
            'unit_number' => $request->unit_number,
            'rent_amount' => $request->rent_amount,
            'status' => $request->status,
            'description' => $request->description,
        ]);

        return redirect()->route('landlord.units.index', $property)
            ->with('success', 'Unit added successfully.');
    }

    // Show edit form
    public function edit(Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);
        $this->authorize('update', $unit);

        return view('landlord.units.edit', compact('property', 'unit'));
    }

    // Update unit
    public function update(UpdateUnitRequest $request, Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);
        $this->authorize('update', $unit);

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
            'status' => $request->status,
            'description' => $request->description,
        ]);

        return redirect()->route('landlord.units.index', $property)
            ->with('success', 'Unit updated successfully.');
    }

    // Delete unit
    public function destroy(Property $property, Unit $unit)
    {
        abort_if($unit->property_id !== $property->id, 404);
        $this->authorize('delete', $unit);

        $unit->delete();

        return back()->with('success', 'Unit deleted successfully.');
    }
}

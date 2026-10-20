<?php

namespace App\Http\Controllers;

use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\User;
use App\Services\UnitCapacityService;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    // List all properties for logged-in landlord
    public function index()
    {
        $this->authorize('viewAny', Property::class);

        $properties = Property::where('user_id', auth()->id())
            ->withCount('units')
            ->latest()
            ->paginate(12);

        return view('landlord.properties.index', compact('properties'));
    }

    // Show create form
    public function create()
    {
        $this->authorize('create', Property::class);

        $caretakers = User::where('role', 'caretaker')->where('is_active', true)->orderBy('name')->get();

        return view('landlord.properties.create', compact('caretakers'));
    }

    // Store new property
    public function store(StorePropertyRequest $request)
    {
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('properties', 'public');
        }

        $property = Property::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'location' => $request->location,
            'description' => $request->description,
            'total_units' => $request->total_units,
            'status' => $request->status,
            'photo' => $photoPath,
        ]);

        $property->caretakers()->sync($request->input('caretakers', []));

        return redirect()->route('landlord.properties.index')
            ->with('success', 'Property created successfully.');
    }

    // Show edit form
    public function edit(Property $property)
    {
        $this->authorize('update', $property);
        $property->loadCount('units');

        $caretakers = User::where('role', 'caretaker')->where('is_active', true)->orderBy('name')->get();

        return view('landlord.properties.edit', compact('property', 'caretakers'));
    }

    // Update property
    public function update(UpdatePropertyRequest $request, Property $property, UnitCapacityService $capacity)
    {
        DB::transaction(function () use ($request, $property, $capacity) {
            $lockedProperty = Property::query()->whereKey($property->id)->lockForUpdate()->firstOrFail();
            $capacity->ensureCapacityCanBeSet($lockedProperty, (int) $request->total_units);
            $photoPath = $lockedProperty->photo;

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('properties', 'public');
            }

            $lockedProperty->update([
                'name' => $request->name,
                'location' => $request->location,
                'description' => $request->description,
                'total_units' => $request->total_units,
                'status' => $request->status,
                'photo' => $photoPath,
            ]);

            $lockedProperty->caretakers()->sync($request->input('caretakers', []));
        });

        return redirect()->route('landlord.properties.index')
            ->with('success', 'Property updated successfully.');
    }

    // Delete property
    public function destroy(Property $property)
    {
        $this->authorize('delete', $property);

        $property->delete();

        return back()->with('success', 'Property deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;

class PropertyController extends Controller
{
    // List all properties for logged-in landlord
    public function index()
    {
        $properties = Property::where('user_id', auth()->id())
                               ->latest()
                               ->get();
        return view('landlord.properties.index', compact('properties'));
    }

    // Show create form
    public function create()
    {
        return view('landlord.properties.create');
    }

    // Store new property
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_units' => 'required|integer|min:1',
            'status'      => 'required|in:active,inactive',
            'photo'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('properties', 'public');
        }

        Property::create([
            'user_id'     => auth()->id(),
            'name'        => $request->name,
            'location'    => $request->location,
            'description' => $request->description,
            'total_units' => $request->total_units,
            'status'      => $request->status,
            'photo'       => $photoPath,
        ]);

        return redirect()->route('landlord.properties.index')
                         ->with('success', 'Property created successfully.');
    }

    // Show edit form
    public function edit(Property $property)
    {
        // Make sure landlord owns this property
        abort_if($property->user_id !== auth()->id(), 403);
        return view('landlord.properties.edit', compact('property'));
    }

    // Update property
    public function update(Request $request, Property $property)
    {
        abort_if($property->user_id !== auth()->id(), 403);

        $request->validate([
            'name'        => 'required|string|max:255',
            'location'    => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_units' => 'required|integer|min:1',
            'status'      => 'required|in:active,inactive',
            'photo'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $photoPath = $property->photo;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('properties', 'public');
        }

        $property->update([
            'name'        => $request->name,
            'location'    => $request->location,
            'description' => $request->description,
            'total_units' => $request->total_units,
            'status'      => $request->status,
            'photo'       => $photoPath,
        ]);

        return redirect()->route('landlord.properties.index')
                         ->with('success', 'Property updated successfully.');
    }

    // Delete property
    public function destroy(Property $property)
    {
        abort_if($property->user_id !== auth()->id(), 403);
        $property->delete();
        return back()->with('success', 'Property deleted successfully.');
    }
}

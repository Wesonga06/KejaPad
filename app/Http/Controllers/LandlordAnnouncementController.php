<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Property;

class LandlordAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::where('user_id', auth()->id())
                                      ->with('property')
                                      ->latest()
                                      ->get();

        return view('landlord.announcements.index', compact('announcements'));
    }

    public function create()
    {
        $properties = Property::where('user_id', auth()->id())->get();
        return view('landlord.announcements.create', compact('properties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'category'    => 'required|in:general,water,electricity,security,maintenance,other',
            'audience'    => 'required|in:all,tenants,caretakers',
            'property_id' => 'nullable|exists:properties,id',
        ]);

        // Make sure landlord owns the selected property
        if ($request->filled('property_id')) {
            $property = Property::findOrFail($request->property_id);
            abort_if($property->user_id !== auth()->id(), 403);
        }

        Announcement::create([
            'user_id'      => auth()->id(),
            'property_id'  => $request->property_id,
            'title'        => $request->title,
            'body'         => $request->body,
            'category'     => $request->category,
            'audience'     => $request->audience,
            'is_published' => true,
            'published_at' => now(),
        ]);

        return redirect()->route('landlord.announcements.index')
                         ->with('success', 'Announcement posted successfully.');
    }

    public function edit(Announcement $announcement)
    {
        abort_if($announcement->user_id !== auth()->id(), 403);
        $properties = Property::where('user_id', auth()->id())->get();
        return view('landlord.announcements.edit', compact('announcement', 'properties'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        abort_if($announcement->user_id !== auth()->id(), 403);

        $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'category'    => 'required|in:general,water,electricity,security,maintenance,other',
            'audience'    => 'required|in:all,tenants,caretakers',
            'property_id' => 'nullable|exists:properties,id',
        ]);

        $announcement->update([
            'property_id' => $request->property_id,
            'title'       => $request->title,
            'body'        => $request->body,
            'category'    => $request->category,
            'audience'    => $request->audience,
        ]);

        return redirect()->route('landlord.announcements.index')
                         ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        abort_if($announcement->user_id !== auth()->id(), 403);
        $announcement->delete();
        return back()->with('success', 'Announcement deleted successfully.');
    }

    public function togglePublish(Announcement $announcement)
    {
        abort_if($announcement->user_id !== auth()->id(), 403);
        $announcement->update(['is_published' => !$announcement->is_published]);
        $status = $announcement->is_published ? 'published' : 'unpublished';
        return back()->with('success', "Announcement {$status} successfully.");
    }
}

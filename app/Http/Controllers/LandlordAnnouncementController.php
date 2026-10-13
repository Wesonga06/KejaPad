<?php

namespace App\Http\Controllers;

use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Property;

class LandlordAnnouncementController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Announcement::class);

        $announcements = Announcement::where('user_id', auth()->id())
            ->with('property')
            ->latest()
            ->paginate(15);

        return view('landlord.announcements.index', compact('announcements'));
    }

    public function create()
    {
        $this->authorize('create', Announcement::class);

        $properties = Property::where('user_id', auth()->id())->get();

        return view('landlord.announcements.create', compact('properties'));
    }

    public function store(StoreAnnouncementRequest $request)
    {
        Announcement::create([
            'user_id' => auth()->id(),
            'property_id' => $request->property_id,
            'title' => $request->title,
            'body' => $request->body,
            'category' => $request->category,
            'audience' => $request->audience,
            'is_published' => true,
            'published_at' => now(),
        ]);

        return redirect()->route('landlord.announcements.index')
            ->with('success', 'Announcement posted successfully.');
    }

    public function edit(Announcement $announcement)
    {
        $this->authorize('update', $announcement);

        $properties = Property::where('user_id', auth()->id())->get();

        return view('landlord.announcements.edit', compact('announcement', 'properties'));
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        $announcement->update([
            'property_id' => $request->property_id,
            'title' => $request->title,
            'body' => $request->body,
            'category' => $request->category,
            'audience' => $request->audience,
        ]);

        return redirect()->route('landlord.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return back()->with('success', 'Announcement deleted successfully.');
    }

    public function togglePublish(Announcement $announcement)
    {
        $this->authorize('togglePublish', $announcement);

        $announcement->update(['is_published' => ! $announcement->is_published]);
        $status = $announcement->is_published ? 'published' : 'unpublished';

        return back()->with('success', "Announcement {$status} successfully.");
    }
}

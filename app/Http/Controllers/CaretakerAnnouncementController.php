<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

class CaretakerAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::where('is_published', true)
            ->whereIn('audience', ['all', 'caretakers'])
            ->latest()
            ->paginate(15);

        return view('caretaker.announcements.index', compact('announcements'));
    }
}

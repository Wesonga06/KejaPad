<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;

class CaretakerAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::where('is_published', true)
                                      ->whereIn('audience', ['all', 'caretakers'])
                                      ->latest()
                                      ->get();

        return view('caretaker.announcements.index', compact('announcements'));
    }
}

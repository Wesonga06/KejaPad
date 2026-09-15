<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Tenant;

class TenantAnnouncementController extends Controller
{
    public function index()
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();

        $announcements = Announcement::where('is_published', true)
                                      ->where(function ($q) use ($tenant) {
                                          $q->where('property_id', $tenant->property_id)
                                            ->orWhereNull('property_id');
                                      })
                                      ->whereIn('audience', ['all', 'tenants'])
                                      ->latest()
                                      ->get();

        return view('tenant.announcements.index', compact('announcements'));
    }
}
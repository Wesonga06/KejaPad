<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PropertyAlert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('manage-settings');

        $base = PropertyAlert::query();

        $status = $request->string('status')->toString() ?: 'open';

        $query = (clone $base)->with(['property.landlord', 'unit', 'tenant']);

        match ($status) {
            'open' => $query->unresolved(),
            'active' => $query->where('status', PropertyAlert::STATUS_ACTIVE),
            'acknowledged' => $query->where('status', PropertyAlert::STATUS_ACKNOWLEDGED),
            'dismissed' => $query->where('status', PropertyAlert::STATUS_DISMISSED),
            'resolved' => $query->where('status', PropertyAlert::STATUS_RESOLVED),
            default => null,
        };

        if ($severity = $request->string('severity')->toString()) {
            $query->where('severity', $severity);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        $alerts = $query
            ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
            ->orderByDesc('last_seen_at')
            ->paginate(25)
            ->withQueryString();

        $summary = [
            'open' => (clone $base)->unresolved()->count(),
            'critical' => (clone $base)->unresolved()->where('severity', PropertyAlert::SEVERITY_CRITICAL)->count(),
            'warning' => (clone $base)->unresolved()->where('severity', PropertyAlert::SEVERITY_WARNING)->count(),
            'info' => (clone $base)->unresolved()->where('severity', PropertyAlert::SEVERITY_INFO)->count(),
            'property_count' => (clone $base)->unresolved()->distinct('property_id')->count('property_id'),
        ];

        return view('super_admin.alerts.index', compact('alerts', 'summary'));
    }

    public function show(PropertyAlert $alert)
    {
        $this->authorize('manage-settings');

        $alert->load(['property.landlord', 'unit', 'tenant', 'subject', 'acknowledgedBy', 'dismissedBy']);

        $events = $alert->events()->with('user')->get();

        return view('super_admin.alerts.show', compact('alert', 'events'));
    }
}

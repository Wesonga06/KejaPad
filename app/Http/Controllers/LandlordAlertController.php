<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyAlert;
use App\Services\AlertService;
use Illuminate\Http\Request;

class LandlordAlertController extends Controller
{
    // Severity order for display: critical first.
    protected const SEVERITY_ORDER = "CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END";

    public function index(Request $request)
    {
        $this->authorize('viewAny', PropertyAlert::class);

        $propertyIds = Property::where('user_id', auth()->id())->pluck('id');

        $base = PropertyAlert::whereIn('property_id', $propertyIds);

        $status = $request->string('status')->toString() ?: 'open';

        $query = (clone $base)->with(['property', 'unit', 'tenant']);

        match ($status) {
            'open' => $query->unresolved(),
            'active' => $query->where('status', PropertyAlert::STATUS_ACTIVE),
            'acknowledged' => $query->where('status', PropertyAlert::STATUS_ACKNOWLEDGED),
            'dismissed' => $query->where('status', PropertyAlert::STATUS_DISMISSED),
            'resolved' => $query->where('status', PropertyAlert::STATUS_RESOLVED),
            default => null,
        };

        if ($propertyId = $request->integer('property_id')) {
            $query->where('property_id', $propertyId);
        }

        if ($severity = $request->string('severity')->toString()) {
            $query->where('severity', $severity);
        }

        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        if ($from = $request->date('from')) {
            $query->where('detected_at', '>=', $from->startOfDay());
        }

        if ($to = $request->date('to')) {
            $query->where('detected_at', '<=', $to->endOfDay());
        }

        $alerts = $query
            ->orderByRaw(self::SEVERITY_ORDER)
            ->orderByDesc('last_seen_at')
            ->paginate(20)
            ->withQueryString();

        // Summary over everything open for this landlord, ignoring the filters.
        $open = (clone $base)->unresolved();

        $summary = [
            'total' => (clone $open)->count(),
            'critical' => (clone $open)->where('severity', PropertyAlert::SEVERITY_CRITICAL)->count(),
            'warning' => (clone $open)->where('severity', PropertyAlert::SEVERITY_WARNING)->count(),
            'info' => (clone $open)->where('severity', PropertyAlert::SEVERITY_INFO)->count(),
        ];

        $properties = Property::where('user_id', auth()->id())->orderBy('name')->get();

        // Escalated maintenance: overdue assigned tasks the accountability engine
        // has flagged. Surfaced here so overdue work is chased alongside warnings.
        $escalatedBase = MaintenanceRequest::whereIn('property_id', $propertyIds)
            ->whereNotNull('escalated_at')
            ->unresolved();

        $escalatedCount = (clone $escalatedBase)->count();

        $escalatedTasks = (clone $escalatedBase)
            ->with(['property', 'unit', 'assignedTo'])
            ->orderByDesc('escalated_at')
            ->take(5)
            ->get();

        return view('landlord.alerts.index', compact('alerts', 'summary', 'properties', 'escalatedCount', 'escalatedTasks'));
    }

    public function show(PropertyAlert $alert)
    {
        $this->authorize('view', $alert);

        $alert->load(['property', 'unit', 'tenant', 'subject', 'acknowledgedBy', 'dismissedBy']);

        $events = $alert->events()->with('user')->get();

        return view('landlord.alerts.show', compact('alert', 'events'));
    }

    public function acknowledge(Request $request, PropertyAlert $alert, AlertService $alerts)
    {
        $this->authorize('manage', $alert);

        $data = $request->validate(['note' => 'nullable|string|max:500']);

        $alerts->acknowledge($alert, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Alert acknowledged.');
    }

    public function dismiss(Request $request, PropertyAlert $alert, AlertService $alerts)
    {
        $this->authorize('manage', $alert);

        $data = $request->validate(['dismissed_reason' => 'required|string|max:500']);

        $alerts->dismiss($alert, $request->user(), $data['dismissed_reason']);

        return back()->with('success', 'Alert dismissed.');
    }

    public function resolve(Request $request, PropertyAlert $alert, AlertService $alerts)
    {
        $this->authorize('manage', $alert);

        $data = $request->validate(['note' => 'nullable|string|max:500']);

        $alerts->resolve($alert, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Alert resolved.');
    }
}

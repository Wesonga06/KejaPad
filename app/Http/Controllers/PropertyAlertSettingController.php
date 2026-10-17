<?php

namespace App\Http\Controllers;

use App\Http\Requests\Property\UpdatePropertyAlertSettingsRequest;
use App\Models\Property;

class PropertyAlertSettingController extends Controller
{
    // Human labels for the overridable threshold keys.
    protected const LABELS = [
        'rent.overdue_min_days' => 'Rent is overdue after (days)',
        'rent.repeated_periods' => 'Repeated arrears after (periods)',
        'maintenance.due_soon_days' => 'Maintenance "due soon" window (days)',
        'maintenance.recurring_count' => 'Recurring maintenance after (requests)',
        'maintenance.recurring_window_days' => 'Recurring maintenance window (days)',
        'inspection.interval_days' => 'Routine inspection interval (days, 0 = off)',
        'inspection.due_soon_days' => 'Inspection "due soon" window (days)',
        'tenancy.end_soon_days' => 'Tenancy ending "soon" window (days)',
        'vacancy.days' => 'Vacancy flagged after (days)',
        'vacancy.critical_days' => 'Vacancy critical after (days)',
        'turnover.stalled_days' => 'Turnover stalled after (days)',
        'settlement.awaiting_days' => 'Settlement awaiting flagged after (days)',
    ];

    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        $keys = config('alerts.overridable', []);
        $overrides = $property->alert_settings ?? [];
        $defaults = [];
        $labels = [];

        foreach ($keys as $key) {
            $defaults[$key] = (int) config("alerts.{$key}", 0);
            $labels[$key] = self::LABELS[$key] ?? ucfirst(str_replace(['.', '_'], [' ', ' '], $key));
        }

        return view('landlord.properties.alerts', compact('property', 'keys', 'overrides', 'defaults', 'labels'));
    }

    public function update(UpdatePropertyAlertSettingsRequest $request, Property $property)
    {
        $this->authorize('update', $property);

        if ($request->boolean('reset')) {
            $property->update(['alert_settings' => null]);

            return redirect()
                ->route('landlord.properties.alerts.settings', $property)
                ->with('success', 'Property alert thresholds reset to the platform defaults.');
        }

        $settings = [];

        foreach (config('alerts.overridable', []) as $key) {
            $value = $request->input($request->fieldName($key));

            if ($value !== null && $value !== '') {
                $settings[$key] = (int) $value;
            }
        }

        $property->update(['alert_settings' => $settings === [] ? null : $settings]);

        return redirect()
            ->route('landlord.properties.alerts.settings', $property)
            ->with('success', 'Alert settings saved for '.$property->name.'.');
    }
}

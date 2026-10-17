<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSystemSettingsRequest;
use App\Services\SettingsService;

class SystemSettingController extends Controller
{
    // Request field => settings key
    protected const MAP = [
        'platform_name' => 'app.name',
        'email_verification' => 'app.email_verification',
        'notice_period_months' => 'tenancy.notice_period_months',
        'deposit_months' => 'tenancy.deposit_months',
        'require_deposit_before_move_in' => 'tenancy.require_deposit_before_move_in',
        'invitation_expiry_days' => 'tenancy.invitation_expiry_days',
        'invitation_single_use' => 'tenancy.invitation_single_use',
        'due_day' => 'rent.due_day',
        'grace_period_days' => 'rent.grace_period_days',
        'allocation_policy' => 'rent.allocation_policy',
        'reminders_enabled' => 'rent.reminders_enabled',
        'notify_tenant' => 'rent.notify_tenant',
        'notify_landlord' => 'rent.notify_landlord',
        'alerts_enabled' => 'alerts.enabled',
        'alerts_rent_overdue_min_days' => 'alerts.rent.overdue_min_days',
        'alerts_rent_repeated_periods' => 'alerts.rent.repeated_periods',
        'alerts_maintenance_due_soon_days' => 'alerts.maintenance.due_soon_days',
        'alerts_maintenance_recurring_count' => 'alerts.maintenance.recurring_count',
        'alerts_inspection_interval_days' => 'alerts.inspection.interval_days',
        'alerts_inspection_due_soon_days' => 'alerts.inspection.due_soon_days',
        'alerts_tenancy_end_soon_days' => 'alerts.tenancy.end_soon_days',
        'alerts_vacancy_days' => 'alerts.vacancy.days',
        'alerts_vacancy_critical_days' => 'alerts.vacancy.critical_days',
        'alerts_turnover_stalled_days' => 'alerts.turnover.stalled_days',
        'alerts_settlement_awaiting_days' => 'alerts.settlement.awaiting_days',
        'alerts_notifications_enabled' => 'alerts.notifications.enabled',
        'alerts_notifications_reminder_interval_days' => 'alerts.notifications.reminder_interval_days',
    ];

    public function index(SettingsService $settings)
    {
        $this->authorize('manage-settings');

        $values = [
            'platform_name' => config('app.name'),
            'email_verification' => (bool) config('app.email_verification'),
            'notice_period_months' => (float) config('tenancy.notice_period_months', 1),
            'deposit_months' => (float) config('tenancy.deposit_months', 1),
            'require_deposit_before_move_in' => (bool) config('tenancy.require_deposit_before_move_in', false),
            'invitation_expiry_days' => (int) config('tenancy.invitation_expiry_days', 7),
            'invitation_single_use' => (bool) config('tenancy.invitation_single_use', true),
            'due_day' => (int) config('rent.due_day', 5),
            'grace_period_days' => (int) config('rent.grace_period_days', 5),
            'allocation_policy' => config('rent.allocation_policy', 'oldest'),
            'reminders_enabled' => (bool) config('rent.reminders_enabled', true),
            'notify_tenant' => (bool) config('rent.notify_tenant', true),
            'notify_landlord' => (bool) config('rent.notify_landlord', true),
            'alerts_enabled' => (bool) config('alerts.enabled', true),
            'alerts_rent_overdue_min_days' => (int) config('alerts.rent.overdue_min_days', 1),
            'alerts_rent_repeated_periods' => (int) config('alerts.rent.repeated_periods', 2),
            'alerts_maintenance_due_soon_days' => (int) config('alerts.maintenance.due_soon_days', 3),
            'alerts_maintenance_recurring_count' => (int) config('alerts.maintenance.recurring_count', 3),
            'alerts_inspection_interval_days' => (int) config('alerts.inspection.interval_days', 180),
            'alerts_inspection_due_soon_days' => (int) config('alerts.inspection.due_soon_days', 14),
            'alerts_tenancy_end_soon_days' => (int) config('alerts.tenancy.end_soon_days', 30),
            'alerts_vacancy_days' => (int) config('alerts.vacancy.days', 30),
            'alerts_vacancy_critical_days' => (int) config('alerts.vacancy.critical_days', 60),
            'alerts_turnover_stalled_days' => (int) config('alerts.turnover.stalled_days', 14),
            'alerts_settlement_awaiting_days' => (int) config('alerts.settlement.awaiting_days', 7),
            'alerts_notifications_enabled' => (bool) config('alerts.notifications.enabled', true),
            'alerts_notifications_reminder_interval_days' => (int) config('alerts.notifications.reminder_interval_days', 7),
        ];

        $stored = $settings->all();
        $overridden = array_keys(array_intersect_key($stored, array_flip(self::MAP)));

        return view('super_admin.settings.system', compact('values', 'stored', 'overridden'));
    }

    public function update(UpdateSystemSettingsRequest $request, SettingsService $settings)
    {
        $payload = [];

        foreach (self::MAP as $field => $key) {
            $payload[$key] = $request->input($field);
        }

        $changed = $settings->update($payload, $request->user(), 'general');

        return redirect()->route('super_admin.settings.index')
            ->with('success', $changed === []
                ? 'Settings saved. No values were changed.'
                : 'Settings saved. '.count($changed).' value(s) updated.');
    }
}

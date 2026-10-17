<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'platform_name' => 'required|string|max:60',
            'email_verification' => 'required|boolean',

            'notice_period_months' => 'required|numeric|min:0|max:6',
            'deposit_months' => 'required|numeric|min:0|max:12',
            'require_deposit_before_move_in' => 'required|boolean',
            'invitation_expiry_days' => 'required|integer|min:1|max:90',
            'invitation_single_use' => 'required|boolean',

            'due_day' => 'required|integer|min:1|max:31',
            'grace_period_days' => 'required|integer|min:0|max:31',
            'allocation_policy' => ['required', Rule::in(['oldest', 'current', 'manual'])],
            'reminders_enabled' => 'required|boolean',
            'notify_tenant' => 'required|boolean',
            'notify_landlord' => 'required|boolean',

            'alerts_enabled' => 'required|boolean',
            'alerts_rent_overdue_min_days' => 'required|integer|min:0|max:365',
            'alerts_rent_repeated_periods' => 'required|integer|min:0|max:36',
            'alerts_maintenance_due_soon_days' => 'required|integer|min:0|max:365',
            'alerts_maintenance_recurring_count' => 'required|integer|min:0|max:100',
            'alerts_inspection_interval_days' => 'required|integer|min:0|max:3650',
            'alerts_inspection_due_soon_days' => 'required|integer|min:0|max:365',
            'alerts_tenancy_end_soon_days' => 'required|integer|min:0|max:365',
            'alerts_vacancy_days' => 'required|integer|min:0|max:3650',
            'alerts_vacancy_critical_days' => 'required|integer|min:0|max:3650',
            'alerts_turnover_stalled_days' => 'required|integer|min:0|max:3650',
            'alerts_settlement_awaiting_days' => 'required|integer|min:0|max:3650',
            'alerts_notifications_enabled' => 'required|boolean',
            'alerts_notifications_reminder_interval_days' => 'required|integer|min:0|max:365',
        ];
    }
}

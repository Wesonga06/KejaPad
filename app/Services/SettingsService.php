<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;

class SettingsService
{
    /*
    |--------------------------------------------------------------------------
    | Known setting keys
    |--------------------------------------------------------------------------
    |
    | Every key below mirrors an existing config() path, so an administrator
    | override is applied over the environment default at boot.
    |
    */
    public const TYPES = [
        'app.name' => 'string',
        'app.email_verification' => 'bool',
        'tenancy.notice_period_months' => 'float',
        'tenancy.deposit_months' => 'float',
        'tenancy.require_deposit_before_move_in' => 'bool',
        'tenancy.invitation_expiry_days' => 'int',
        'tenancy.invitation_single_use' => 'bool',
        'rent.due_day' => 'int',
        'rent.grace_period_days' => 'int',
        'rent.allocation_policy' => 'string',
        'rent.notify_tenant' => 'bool',
        'rent.notify_landlord' => 'bool',
        'rent.reminders_enabled' => 'bool',
        'mpesa.callback_enforce_ip' => 'bool',
        'mpesa.callback_allowed_ips' => 'array',
        'payments.methods_enabled' => 'array',
        'payments.require_verification' => 'bool',
        'sms.provider' => 'string',
        'sms.from' => 'string',
        'sms.workflow_enabled' => 'bool',
        'alerts.enabled' => 'bool',
        'alerts.rent.overdue_min_days' => 'int',
        'alerts.rent.repeated_periods' => 'int',
        'alerts.maintenance.due_soon_days' => 'int',
        'alerts.maintenance.recurring_count' => 'int',
        'alerts.maintenance.recurring_window_days' => 'int',
        'alerts.inspection.interval_days' => 'int',
        'alerts.inspection.due_soon_days' => 'int',
        'alerts.tenancy.end_soon_days' => 'int',
        'alerts.vacancy.days' => 'int',
        'alerts.vacancy.critical_days' => 'int',
        'alerts.turnover.stalled_days' => 'int',
        'alerts.settlement.awaiting_days' => 'int',
        'alerts.notifications.enabled' => 'bool',
        'alerts.notifications.reminder_interval_days' => 'int',
    ];

    // Keys that are written into config() at boot.
    public const BRIDGED = [
        'app.name',
        'app.email_verification',
        'tenancy.notice_period_months',
        'tenancy.deposit_months',
        'tenancy.require_deposit_before_move_in',
        'tenancy.invitation_expiry_days',
        'tenancy.invitation_single_use',
        'rent.due_day',
        'rent.grace_period_days',
        'rent.allocation_policy',
        'rent.notify_tenant',
        'rent.notify_landlord',
        'rent.reminders_enabled',
        'mpesa.callback_enforce_ip',
        'mpesa.callback_allowed_ips',
        'payments.methods_enabled',
        'payments.require_verification',
        'sms.provider',
        'sms.from',
        'sms.workflow_enabled',
        'alerts.enabled',
        'alerts.rent.overdue_min_days',
        'alerts.rent.repeated_periods',
        'alerts.maintenance.due_soon_days',
        'alerts.maintenance.recurring_count',
        'alerts.maintenance.recurring_window_days',
        'alerts.inspection.interval_days',
        'alerts.inspection.due_soon_days',
        'alerts.tenancy.end_soon_days',
        'alerts.vacancy.days',
        'alerts.vacancy.critical_days',
        'alerts.turnover.stalled_days',
        'alerts.settlement.awaiting_days',
        'alerts.notifications.enabled',
        'alerts.notifications.reminder_interval_days',
    ];

    // Apply stored administrator overrides over the environment config.
    public function applyToConfig(): void
    {
        foreach ($this->stored() as $setting) {
            if (! in_array($setting->key, self::BRIDGED, true)) {
                continue;
            }

            config([$setting->key => $this->cast($setting->value, self::TYPES[$setting->key] ?? 'string')]);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        foreach ($this->stored() as $setting) {
            if ($setting->key === $key) {
                return $this->cast($setting->value, self::TYPES[$setting->key] ?? $setting->type ?? 'string');
            }
        }

        return $default;
    }

    public function all(): array
    {
        $values = [];

        foreach ($this->stored() as $setting) {
            $values[$setting->key] = $this->cast(
                $setting->value,
                self::TYPES[$setting->key] ?? $setting->type ?? 'string'
            );
        }

        return $values;
    }

    // Persist a batch of settings and write one audit entry for the diff.
    public function update(array $values, ?User $actor = null, string $group = 'general'): array
    {
        $changed = [];

        foreach ($values as $key => $value) {
            $type = self::TYPES[$key] ?? 'string';
            $normalized = $this->normalize($value, $type);

            $current = Setting::where('key', $key)->first();

            if ($current !== null && $current->value === $normalized) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $normalized, 'group' => $group, 'type' => $type]
            );

            $changed[$key] = ['old' => $current?->value, 'new' => $normalized];
        }

        if ($changed !== []) {
            $this->writeAudit($changed, $actor);
        }

        return $changed;
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
    }

    protected function writeAudit(array $changed, ?User $actor): void
    {
        AuditLog::create([
            'user_id' => ($actor ?? auth()->user())?->id,
            'action' => 'settings.updated',
            'description' => 'Updated settings: '.implode(', ', array_keys($changed)),
            'old_values' => array_map(fn ($diff) => $diff['old'], $changed),
            'new_values' => array_map(fn ($diff) => $diff['new'], $changed),
            'ip_address' => request()?->ip(),
        ]);
    }

    protected function normalize(mixed $value, string $type): ?string
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'int' => (string) (int) $value,
            'float' => (string) (float) $value,
            'array' => json_encode($value, JSON_THROW_ON_ERROR),
            default => $value === null ? null : (string) $value,
        };
    }

    protected function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'float' => (float) $value,
            'array' => json_decode($value ?? '[]', true) ?? [],
            default => $value,
        };
    }

    // Load every stored setting. Returns an empty collection when the settings
    // table is not available yet (for example during an early boot or a fresh
    // install) so callers always fall back to the environment config.
    protected function stored(): Collection
    {
        try {
            return Setting::query()->get();
        } catch (\Throwable) {
            return collect();
        }
    }
}

@extends('layouts.app')

@section('page-title', 'System Configuration')

@php
    $isCustom = fn (string $key) => in_array($key, $overridden, true);
@endphp

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">System Configuration</h1>
            <p class="kp-page-subtitle">Platform-wide defaults applied to new records across KejaPad.</p>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-start" role="alert">
        <i class="bi bi-shield-check me-2 mt-1"></i>
        <div>
            These values are global defaults. Existing tenancy agreements, announcements and policy already
            snapshotted per agreement (for example a deposit's policy months) are never altered retroactively.
        </div>
    </div>

    <form method="POST" action="{{ route('super_admin.settings.update') }}">
        @csrf

        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Platform</h2>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Platform name
                        @if($isCustom('app.name')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="text" name="platform_name" class="form-control @error('platform_name') is-invalid @enderror"
                           value="{{ old('platform_name', $values['platform_name']) }}" maxlength="60">
                    @error('platform_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold d-block">
                        Email verification
                        @if($isCustom('app.email_verification')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="hidden" name="email_verification" value="0">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="email_verification" value="1" id="email_verification"
                               class="form-check-input" {{ old('email_verification', $values['email_verification']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="email_verification">Require verified email before access</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Tenancy defaults</h2>
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Notice period (months)
                        @if($isCustom('tenancy.notice_period_months')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" step="0.5" min="0" max="6" name="notice_period_months"
                           class="form-control @error('notice_period_months') is-invalid @enderror"
                           value="{{ old('notice_period_months', $values['notice_period_months']) }}">
                    @error('notice_period_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Deposit (months of rent)
                        @if($isCustom('tenancy.deposit_months')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" step="0.5" min="0" max="12" name="deposit_months"
                           class="form-control @error('deposit_months') is-invalid @enderror"
                           value="{{ old('deposit_months', $values['deposit_months']) }}">
                    @error('deposit_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Invitation expiry (days)
                        @if($isCustom('tenancy.invitation_expiry_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="1" max="90" name="invitation_expiry_days"
                           class="form-control @error('invitation_expiry_days') is-invalid @enderror"
                           value="{{ old('invitation_expiry_days', $values['invitation_expiry_days']) }}">
                    @error('invitation_expiry_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3 d-flex flex-column justify-content-end">
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="require_deposit_before_move_in" value="0">
                        <input type="checkbox" name="require_deposit_before_move_in" value="1" id="require_deposit_before_move_in"
                               class="form-check-input" {{ old('require_deposit_before_move_in', $values['require_deposit_before_move_in']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="require_deposit_before_move_in">Require deposit before move-in</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="invitation_single_use" value="0">
                        <input type="checkbox" name="invitation_single_use" value="1" id="invitation_single_use"
                               class="form-check-input" {{ old('invitation_single_use', $values['invitation_single_use']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="invitation_single_use">Caretaker invitations single-use</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Rent defaults</h2>
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Rent due day
                        @if($isCustom('rent.due_day')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="1" max="31" name="due_day"
                           class="form-control @error('due_day') is-invalid @enderror"
                           value="{{ old('due_day', $values['due_day']) }}">
                    @error('due_day') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Grace period (days)
                        @if($isCustom('rent.grace_period_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="31" name="grace_period_days"
                           class="form-control @error('grace_period_days') is-invalid @enderror"
                           value="{{ old('grace_period_days', $values['grace_period_days']) }}">
                    @error('grace_period_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        Default allocation policy
                        @if($isCustom('rent.allocation_policy')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <select name="allocation_policy" class="form-select @error('allocation_policy') is-invalid @enderror">
                        @foreach(['oldest' => 'Oldest balance first', 'current' => 'Current period first', 'manual' => 'Manual selection'] as $value => $label)
                            <option value="{{ $value }}" {{ old('allocation_policy', $values['allocation_policy']) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('allocation_policy') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold d-block">Notifications</label>
                    <div class="d-flex flex-wrap gap-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="reminders_enabled" value="0">
                            <input type="checkbox" name="reminders_enabled" value="1" id="reminders_enabled"
                                   class="form-check-input" {{ old('reminders_enabled', $values['reminders_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="reminders_enabled">Automated rent reminders</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="notify_tenant" value="0">
                            <input type="checkbox" name="notify_tenant" value="1" id="notify_tenant"
                                   class="form-check-input" {{ old('notify_tenant', $values['notify_tenant']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_tenant">Notify tenants</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="notify_landlord" value="0">
                            <input type="checkbox" name="notify_landlord" value="1" id="notify_landlord"
                                   class="form-check-input" {{ old('notify_landlord', $values['notify_landlord']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="notify_landlord">Notify landlords</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Early-warning alerts</h2>
            </div>
            <p class="text-muted" style="font-size:0.85rem;">
                Thresholds used by the daily early-warning engine. Landlords may override some of these per property.
            </p>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold d-block">Alerts</label>
                    <div class="d-flex flex-wrap gap-4">
                        <div class="form-check form-switch">
                            <input type="hidden" name="alerts_enabled" value="0">
                            <input type="checkbox" name="alerts_enabled" value="1" id="alerts_enabled"
                                   class="form-check-input" {{ old('alerts_enabled', $values['alerts_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="alerts_enabled">Enable early-warning alerts</label>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="alerts_notifications_enabled" value="0">
                            <input type="checkbox" name="alerts_notifications_enabled" value="1" id="alerts_notifications_enabled"
                                   class="form-check-input" {{ old('alerts_notifications_enabled', $values['alerts_notifications_enabled']) ? 'checked' : '' }}>
                            <label class="form-check-label" for="alerts_notifications_enabled">Notify landlords</label>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Rent overdue after (days)
                        @if($isCustom('alerts.rent.overdue_min_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="365" name="alerts_rent_overdue_min_days"
                           class="form-control @error('alerts_rent_overdue_min_days') is-invalid @enderror"
                           value="{{ old('alerts_rent_overdue_min_days', $values['alerts_rent_overdue_min_days']) }}">
                    @error('alerts_rent_overdue_min_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Repeated arrears after (periods)
                        @if($isCustom('alerts.rent.repeated_periods')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="36" name="alerts_rent_repeated_periods"
                           class="form-control @error('alerts_rent_repeated_periods') is-invalid @enderror"
                           value="{{ old('alerts_rent_repeated_periods', $values['alerts_rent_repeated_periods']) }}">
                    @error('alerts_rent_repeated_periods') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Maintenance due soon (days)
                        @if($isCustom('alerts.maintenance.due_soon_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="365" name="alerts_maintenance_due_soon_days"
                           class="form-control @error('alerts_maintenance_due_soon_days') is-invalid @enderror"
                           value="{{ old('alerts_maintenance_due_soon_days', $values['alerts_maintenance_due_soon_days']) }}">
                    @error('alerts_maintenance_due_soon_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Recurring maintenance after (requests)
                        @if($isCustom('alerts.maintenance.recurring_count')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="100" name="alerts_maintenance_recurring_count"
                           class="form-control @error('alerts_maintenance_recurring_count') is-invalid @enderror"
                           value="{{ old('alerts_maintenance_recurring_count', $values['alerts_maintenance_recurring_count']) }}">
                    @error('alerts_maintenance_recurring_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Inspection interval (days, 0 = off)
                        @if($isCustom('alerts.inspection.interval_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="3650" name="alerts_inspection_interval_days"
                           class="form-control @error('alerts_inspection_interval_days') is-invalid @enderror"
                           value="{{ old('alerts_inspection_interval_days', $values['alerts_inspection_interval_days']) }}">
                    @error('alerts_inspection_interval_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Inspection due soon (days)
                        @if($isCustom('alerts.inspection.due_soon_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="365" name="alerts_inspection_due_soon_days"
                           class="form-control @error('alerts_inspection_due_soon_days') is-invalid @enderror"
                           value="{{ old('alerts_inspection_due_soon_days', $values['alerts_inspection_due_soon_days']) }}">
                    @error('alerts_inspection_due_soon_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Tenancy ending soon (days)
                        @if($isCustom('alerts.tenancy.end_soon_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="365" name="alerts_tenancy_end_soon_days"
                           class="form-control @error('alerts_tenancy_end_soon_days') is-invalid @enderror"
                           value="{{ old('alerts_tenancy_end_soon_days', $values['alerts_tenancy_end_soon_days']) }}">
                    @error('alerts_tenancy_end_soon_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Vacancy flagged after (days)
                        @if($isCustom('alerts.vacancy.days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="3650" name="alerts_vacancy_days"
                           class="form-control @error('alerts_vacancy_days') is-invalid @enderror"
                           value="{{ old('alerts_vacancy_days', $values['alerts_vacancy_days']) }}">
                    @error('alerts_vacancy_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Vacancy critical after (days)
                        @if($isCustom('alerts.vacancy.critical_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="3650" name="alerts_vacancy_critical_days"
                           class="form-control @error('alerts_vacancy_critical_days') is-invalid @enderror"
                           value="{{ old('alerts_vacancy_critical_days', $values['alerts_vacancy_critical_days']) }}">
                    @error('alerts_vacancy_critical_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Turnover stalled after (days)
                        @if($isCustom('alerts.turnover.stalled_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="3650" name="alerts_turnover_stalled_days"
                           class="form-control @error('alerts_turnover_stalled_days') is-invalid @enderror"
                           value="{{ old('alerts_turnover_stalled_days', $values['alerts_turnover_stalled_days']) }}">
                    @error('alerts_turnover_stalled_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Settlement awaiting after (days)
                        @if($isCustom('alerts.settlement.awaiting_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="3650" name="alerts_settlement_awaiting_days"
                           class="form-control @error('alerts_settlement_awaiting_days') is-invalid @enderror"
                           value="{{ old('alerts_settlement_awaiting_days', $values['alerts_settlement_awaiting_days']) }}">
                    @error('alerts_settlement_awaiting_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold">
                        Re-notify after (days)
                        @if($isCustom('alerts.notifications.reminder_interval_days')) <span class="kp-badge kp-badge-gold ms-1">custom</span> @endif
                    </label>
                    <input type="number" min="0" max="365" name="alerts_notifications_reminder_interval_days"
                           class="form-control @error('alerts_notifications_reminder_interval_days') is-invalid @enderror"
                           value="{{ old('alerts_notifications_reminder_interval_days', $values['alerts_notifications_reminder_interval_days']) }}">
                    @error('alerts_notifications_reminder_interval_days') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-2"></i> Save Configuration
            </button>
        </div>
    </form>
@endsection

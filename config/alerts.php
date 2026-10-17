<?php

/*
|--------------------------------------------------------------------------
| Early-warning alert rules
|--------------------------------------------------------------------------
|
| Every threshold used by the rule-based early-warning engine. These are
| plain business thresholds derived from records the platform already holds;
| they never override the payment, deposit or legal rules enforced elsewhere.
| A property may override a curated subset of these values (see OVERRIDABLE)
| through its own settings; anything not overridden falls back to the value
| defined here (which a Super Administrator may, in turn, override globally).
|
*/

return [
    // Master switch for generating alerts on the schedule.
    'enabled' => filter_var(env('ALERTS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // A rent period that is at least this many days overdue raises a warning.
    'rent' => [
        'overdue_min_days' => (int) env('ALERTS_RENT_OVERDUE_MIN_DAYS', 1),
        'repeated_periods' => (int) env('ALERTS_RENT_REPEATED_PERIODS', 2),
        'repeated_window_months' => (int) env('ALERTS_RENT_REPEATED_WINDOW_MONTHS', 6),
        'critical_after_days' => (int) env('ALERTS_RENT_CRITICAL_DAYS', 30),
    ],

    // Maintenance works (excluding turnover repair tasks, which are covered by
    // the turnover rules) that are due soon, overdue, or recurring.
    'maintenance' => [
        'due_soon_days' => (int) env('ALERTS_MAINTENANCE_DUE_SOON_DAYS', 3),
        'overdue_critical_days' => (int) env('ALERTS_MAINTENANCE_OVERDUE_CRITICAL_DAYS', 7),
        'recurring_count' => (int) env('ALERTS_MAINTENANCE_RECURRING_COUNT', 3),
        'recurring_window_days' => (int) env('ALERTS_MAINTENANCE_RECURRING_WINDOW_DAYS', 90),
    ],

    // Routine inspections. An interval of 0 disables inspection alerts.
    'inspection' => [
        'interval_days' => (int) env('ALERTS_INSPECTION_INTERVAL_DAYS', 180),
        'due_soon_days' => (int) env('ALERTS_INSPECTION_DUE_SOON_DAYS', 14),
    ],

    // Tenancies whose recorded lease end date is approaching or passed.
    'tenancy' => [
        'end_soon_days' => (int) env('ALERTS_TENANCY_END_SOON_DAYS', 30),
        'critical_after_days' => (int) env('ALERTS_TENANCY_CRITICAL_AFTER_DAYS', 90),
    ],

    // Units left vacant (a vacancy is only detected where a move-out date is
    // recorded, so the duration is factual rather than guessed).
    'vacancy' => [
        'days' => (int) env('ALERTS_VACANCY_DAYS', 30),
        'critical_days' => (int) env('ALERTS_VACANCY_CRITICAL_DAYS', 60),
    ],

    // Turnover workspaces with no recorded activity for a while.
    'turnover' => [
        'stalled_days' => (int) env('ALERTS_TURNOVER_STALLED_DAYS', 14),
        'critical_days' => (int) env('ALERTS_TURNOVER_CRITICAL_DAYS', 30),
    ],

    // Deposit settlements still waiting on a tenant response or landlord decision.
    'settlement' => [
        'awaiting_days' => (int) env('ALERTS_SETTLEMENT_AWAITING_DAYS', 7),
        'critical_days' => (int) env('ALERTS_SETTLEMENT_CRITICAL_DAYS', 21),
    ],

    'notifications' => [
        'enabled' => filter_var(env('ALERTS_NOTIFICATIONS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        // Minimum days between repeat notifications for the same alert.
        'reminder_interval_days' => (int) env('ALERTS_REMINDER_INTERVAL_DAYS', 7),
    ],

    // Curated keys a landlord may override per property. Values must be
    // non-negative integers; a value of 0 disables that threshold.
    'overridable' => [
        'rent.overdue_min_days',
        'rent.repeated_periods',
        'maintenance.due_soon_days',
        'maintenance.recurring_count',
        'maintenance.recurring_window_days',
        'inspection.interval_days',
        'inspection.due_soon_days',
        'tenancy.end_soon_days',
        'vacancy.days',
        'vacancy.critical_days',
        'turnover.stalled_days',
        'settlement.awaiting_days',
    ],
];

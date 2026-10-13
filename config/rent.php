<?php

return [
    // Days after the due date before a rent period is treated as overdue.
    'grace_period_days' => (int) env('RENT_GRACE_PERIOD_DAYS', 0),

    // Day of the month rent falls due (3 = rent is payable by the 3rd).
    'due_day' => (int) env('RENT_DUE_DAY', 3),

    // Automatically generate a rent record for every active tenant at the start of each month.
    'auto_generate' => filter_var(env('RENT_AUTO_GENERATE', true), FILTER_VALIDATE_BOOLEAN),

    // Automatically apply unallocated payment credit to outstanding rent periods.
    'apply_credit' => filter_var(env('RENT_APPLY_CREDIT', true), FILTER_VALIDATE_BOOLEAN),

    // Default allocation policy: oldest | current | manual
    'allocation_policy' => env('RENT_ALLOCATION_POLICY', 'oldest'),

    // Colour-coded arrears thresholds (in days overdue).
    'thresholds' => [
        'orange_after_days' => (int) env('RENT_ORANGE_AFTER_DAYS', 14),
        'yellow_after_days' => (int) env('RENT_YELLOW_AFTER_DAYS', 30),
    ],

    // Derived rent statuses (never stored; always recalculated from financial records).
    'statuses' => [
        'green' => ['label' => 'Up to date', 'color' => '#198754', 'bootstrap' => 'success'],
        'red' => ['label' => 'Overdue', 'color' => '#dc3545', 'bootstrap' => 'danger'],
        'orange' => ['label' => 'Escalated arrears', 'color' => '#fd7e14', 'bootstrap' => 'warning'],
        'yellow' => ['label' => 'Further action required', 'color' => '#b8860b', 'bootstrap' => 'warning'],
    ],

    // Reminder schedule: notice type => days overdue threshold at which it applies.
    'reminder_schedule' => [
        'payment_reminder' => (int) env('RENT_REMINDER_STAGE1_DAYS', 1),
        'follow_up_reminder' => (int) env('RENT_REMINDER_STAGE2_DAYS', 7),
        'escalation' => (int) env('RENT_REMINDER_STAGE3_DAYS', 21),
    ],

    // Escalate the account to the landlord once this many days overdue.
    'escalate_after_days' => (int) env('RENT_ESCALATE_AFTER_DAYS', 21),

    // Minimum days between two reminders of the same type for the same tenant.
    'reminder_cooldown_days' => (int) env('RENT_REMINDER_COOLDOWN_DAYS', 3),

    // Whether the reminder run dispatches tenant / landlord notifications.
    'notify_tenant' => filter_var(env('RENT_NOTIFY_TENANT', true), FILTER_VALIDATE_BOOLEAN),
    'notify_landlord' => filter_var(env('RENT_NOTIFY_LANDLORD', true), FILTER_VALIDATE_BOOLEAN),

    // Master switch for the scheduled reminder run.
    'reminders_enabled' => filter_var(env('RENT_REMINDERS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];

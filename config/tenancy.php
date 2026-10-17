<?php

return [
    // Minimum notice a tenant must give before moving out, in calendar months.
    'notice_period_months' => (float) env('TENANCY_NOTICE_PERIOD_MONTHS', 1),

    // Security deposit policy: number of months' rent held as a deposit.
    'deposit_months' => (float) env('TENANCY_DEPOSIT_MONTHS', 1),

    // When true, a tenancy cannot be marked as moved-in (unit occupied) until
    // the required security deposit has been received and verified.
    'require_deposit_before_move_in' => filter_var(
        env('TENANCY_REQUIRE_DEPOSIT_MOVE_IN', true),
        FILTER_VALIDATE_BOOLEAN
    ),

    // Caretaker invitations -------------------------------------------------

    // Number of days a caretaker invitation remains valid after being issued.
    'invitation_expiry_days' => (int) env('TENANCY_INVITATION_EXPIRY_DAYS', 7),

    // When true (default) an invitation may only be redeemed once.
    'invitation_single_use' => filter_var(
        env('TENANCY_INVITATION_SINGLE_USE', true),
        FILTER_VALIDATE_BOOLEAN
    ),
];

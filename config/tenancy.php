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
];

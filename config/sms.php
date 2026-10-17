<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Provider
    |--------------------------------------------------------------------------
    |
    | KejaPad ships without a live SMS provider. Set SMS_PROVIDER to one of
    | "null" (disabled), "log" (writes messages to the log for local testing)
    | or "africastalking" (Africa's Talking REST API) and supply credentials
    | through environment variables. Credentials are never stored in the
    | database and never exposed to the frontend.
    |
    */
    'provider' => env('SMS_PROVIDER', 'null'),

    // Default sender ID / short code (provider dependent).
    'from' => env('SMS_FROM'),

    // HTTP timeout in seconds.
    'timeout' => (int) env('SMS_TIMEOUT', 15),

    // Master switch used by automated workflows (reminders, invitations,
    // approvals). Manual test messages are always allowed.
    'workflow_enabled' => filter_var(env('SMS_WORKFLOW_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'africastalking' => [
        'username' => env('AFRICASTALKING_USERNAME'),
        'api_key' => env('AFRICASTALKING_API_KEY'),
        'base_url' => env('AFRICASTALKING_BASE_URL', 'https://api.africastalking.com/version1'),
    ],
];

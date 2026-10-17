<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled payment methods
    |--------------------------------------------------------------------------
    |
    | Platform-wide default. Individual payment recording forms refuse methods
    | that are switched off here. Can be overridden by the super administrator
    | (System > Payment Settings) through the settings table.
    |
    */

    'methods_enabled' => array_filter(explode(',', (string) env('PAYMENTS_METHODS_ENABLED', 'mpesa,cash,bank_transfer,cheque,other'))),

    'methods' => [
        'mpesa' => 'M-Pesa',
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment verification
    |--------------------------------------------------------------------------
    |
    | When true, M-Pesa callbacks confirm the transaction but do not credit the
    | rent record automatically - the landlord must confirm (or reject) the
    | payment first. Off by default: automatic crediting remains the default
    | behaviour.
    |
    */

    'require_verification' => (bool) env('PAYMENTS_REQUIRE_VERIFICATION', false),

];

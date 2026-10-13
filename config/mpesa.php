<?php

return [
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode' => env('MPESA_SHORTCODE', '174379'),
    'passkey' => env('MPESA_PASSKEY'),
    'callback_url' => env('MPESA_CALLBACK_URL'),
    'env' => env('MPESA_ENV', 'sandbox'),

    'callback_allowed_ips' => array_filter(array_map('trim', explode(',', (string) env('MPESA_CALLBACK_ALLOWED_IPS',
        '196.201.214.200,196.201.214.206,196.201.214.207,196.201.214.208,196.201.213.114,196.201.214.205,196.201.212.127,196.201.212.128,196.201.212.129,196.201.212.132,196.201.212.136,196.201.212.138'
    )))),

    'callback_enforce_ip' => env('MPESA_CALLBACK_ENFORCE_IP', true),
];

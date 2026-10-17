<?php

namespace App\Services\Sms;

// Used when SMS_PROVIDER is not configured. Nothing is sent and no message
// ever leaves the system.
class NullDriver implements SmsDriver
{
    public function send(string $to, string $body, string $from = ''): array
    {
        return [
            'status' => 'failed',
            'provider_message_id' => null,
            'error' => 'No SMS provider configured. Set SMS_PROVIDER (and provider credentials) in your environment.',
        ];
    }
}

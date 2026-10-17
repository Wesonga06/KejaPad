<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

// Local development driver: records the message in the application log so
// integrations can be exercised without contacting a real provider.
class LogDriver implements SmsDriver
{
    public function send(string $to, string $body, string $from = ''): array
    {
        Log::info('SMS (log driver)', [
            'to' => $to,
            'from' => $from,
            'body' => $body,
        ]);

        return [
            'status' => 'sent',
            'provider_message_id' => null,
            'error' => null,
        ];
    }
}

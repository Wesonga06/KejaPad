<?php

namespace App\Services\Sms;

interface SmsDriver
{
    /**
     * Attempt to deliver a single message.
     *
     * @return array{status: string, provider_message_id: ?string, error: ?string}
     */
    public function send(string $to, string $body, string $from = ''): array;
}

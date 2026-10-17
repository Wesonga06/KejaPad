<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

// Africa's Talking REST messaging API.
class AfricasTalkingDriver implements SmsDriver
{
    public function __construct(
        protected string $username,
        protected string $apiKey,
        protected string $baseUrl,
        protected int $timeout = 15,
    ) {}

    public function send(string $to, string $body, string $from = ''): array
    {
        if ($this->username === '' || $this->apiKey === '') {
            return [
                'status' => 'failed',
                'provider_message_id' => null,
                'error' => 'Africa\'s Talking credentials are missing (AFRICASTALKING_USERNAME / AFRICASTALKING_API_KEY).',
            ];
        }

        $response = Http::withHeaders([
            'apiKey' => $this->apiKey,
            'Accept' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->asForm()
            ->post(rtrim($this->baseUrl, '/').'/messaging', array_filter([
                'username' => $this->username,
                'to' => $this->normalize($to),
                'message' => $body,
                'from' => $from !== '' ? $from : null,
            ], fn ($value) => $value !== null && $value !== ''));

        if ($response->failed()) {
            return [
                'status' => 'failed',
                'provider_message_id' => null,
                'error' => 'Provider HTTP '.$response->status().': '.$response->body(),
            ];
        }

        $recipient = $response->json('SMSMessageData.Recipients.0') ?? [];

        $statusCode = $recipient['statusCode'] ?? 0;
        $statusText = $recipient['status'] ?? ($recipient['errorMessage'] ?? 'Unknown provider status');

        return [
            'status' => $statusCode === 100 || $statusCode === 101 ? 'sent' : 'failed',
            'provider_message_id' => $recipient['messageId'] ?? null,
            'error' => $statusCode === 100 || $statusCode === 101 ? null : (string) $statusText,
        ];
    }

    // 0712345678 / 254712345678 -> +254712345678
    protected function normalize(string $to): string
    {
        $digits = preg_replace('/\D+/', '', $to);

        if (str_starts_with($digits, '0')) {
            $digits = '254'.substr($digits, 1);
        }

        return '+'.ltrim($digits, '+');
    }
}

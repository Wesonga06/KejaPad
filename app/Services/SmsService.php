<?php

namespace App\Services;

use App\Models\SmsMessage;
use App\Models\SmsTemplate;
use App\Services\Sms\AfricasTalkingDriver;
use App\Services\Sms\LogDriver;
use App\Services\Sms\NullDriver;
use App\Services\Sms\SmsDriver;
use Throwable;

class SmsService
{
    public const PROVIDERS = [
        'null' => 'Not configured (SMS disabled)',
        'log' => 'Log (local testing only)',
        'africastalking' => 'Africa\'s Talking',
    ];

    public function providerName(): string
    {
        return (string) config('sms.provider', 'null');
    }

    // True when a provider is selected (the message may actually be attempted).
    public function isConfigured(): bool
    {
        return $this->providerName() !== 'null';
    }

    // Whether this environment holds the credentials the selected provider needs.
    // Only booleans are exposed - never the credential values themselves.
    public function hasCredentials(): bool
    {
        return match ($this->providerName()) {
            'africastalking' => filled(config('sms.africastalking.username'))
                && filled(config('sms.africastalking.api_key')),
            'log' => true,
            default => false,
        };
    }

    public function driver(): SmsDriver
    {
        return match ($this->providerName()) {
            'log' => new LogDriver,
            'africastalking' => new AfricasTalkingDriver(
                (string) config('sms.africastalking.username'),
                (string) config('sms.africastalking.api_key'),
                (string) config('sms.africastalking.base_url'),
                (int) config('sms.timeout', 15),
            ),
            default => new NullDriver,
        };
    }

    // Manual / explicit send. Always attempts so administrators receive
    // genuine feedback (including a clear failure when unconfigured).
    public function send(string $recipient, string $body, array $options = []): SmsMessage
    {
        $related = $options['related'] ?? null;

        $message = SmsMessage::create([
            'recipient' => $recipient,
            'body' => $body,
            'template' => $options['template'] ?? null,
            'provider' => $this->providerName(),
            'status' => SmsMessage::STATUS_QUEUED,
            'sent_by' => $options['sent_by'] ?? null,
            'related_type' => $related?->getMorphClass(),
            'related_id' => $related?->getKey(),
        ]);

        try {
            $result = $this->driver()->send($recipient, $body, (string) config('sms.from', ''));
        } catch (Throwable $e) {
            $result = [
                'status' => SmsMessage::STATUS_FAILED,
                'provider_message_id' => null,
                'error' => $e->getMessage(),
            ];
        }

        $message->update([
            'status' => $result['status'] ?? SmsMessage::STATUS_FAILED,
            'provider_message_id' => $result['provider_message_id'] ?? null,
            'error' => $result['error'] ?? null,
        ]);

        return $message->refresh();
    }

    // Workflow send (reminders, approvals, invitations). Silent when SMS is
    // disabled for workflows or no provider is configured, so automated runs
    // never emit misleading "sent" records without a provider.
    public function sendTemplate(string $key, string $recipient, array $vars = [], array $options = []): ?SmsMessage
    {
        if (! config('sms.workflow_enabled', true) || ! $this->isConfigured()) {
            return null;
        }

        if (blank($recipient)) {
            return null;
        }

        $template = SmsTemplate::where('key', $key)->first();

        if ($template === null) {
            return null;
        }

        return $this->send($recipient, $template->render($vars), [
            'template' => $key,
            'sent_by' => $options['sent_by'] ?? null,
            'related' => $options['related'] ?? null,
        ]);
    }

    // Delivery statistics for the SMS report / settings pages.
    public function stats(): array
    {
        return [
            'total' => SmsMessage::count(),
            'sent' => SmsMessage::where('status', SmsMessage::STATUS_SENT)->count(),
            'failed' => SmsMessage::where('status', SmsMessage::STATUS_FAILED)->count(),
            'queued' => SmsMessage::where('status', SmsMessage::STATUS_QUEUED)->count(),
        ];
    }
}

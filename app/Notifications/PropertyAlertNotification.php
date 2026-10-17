<?php

namespace App\Notifications;

use App\Models\PropertyAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies a landlord that an early-warning alert needs their attention. The
 * notification links straight to the alert so the underlying records can be
 * checked; it never mutates the records itself.
 */
class PropertyAlertNotification extends Notification
{
    use Queueable;

    public function __construct(public PropertyAlert $alert) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function title(): string
    {
        return '['.strtoupper($this->alert->severity).'] '.$this->alert->title;
    }

    public function viaUrl(object $notifiable): string
    {
        return route('landlord.alerts.show', $this->alert);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unit = $this->alert->unit?->unit_number;

        return (new MailMessage)
            ->subject($this->title())
            ->line($this->alert->reason)
            ->when($unit, fn (MailMessage $mail) => $mail->line('Unit: '.$unit))
            ->line('Recommended action: '.($this->alert->recommended_action ?? 'Review this alert.'))
            ->action('Open alert', $this->viaUrl($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->alert->reason,
            'severity' => $this->alert->severity,
            'type' => $this->alert->type,
            'property_alert_id' => $this->alert->id,
            'url' => $this->viaUrl($notifiable),
        ];
    }
}

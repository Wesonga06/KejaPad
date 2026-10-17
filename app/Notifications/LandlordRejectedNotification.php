<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandlordRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(public User $landlord, public string $reason) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your KejaPad landlord application was not approved')
            ->greeting('Hello '.$this->landlord->name.',')
            ->line('After review, your landlord application was not approved.')
            ->line('Reason: '.$this->reason)
            ->line('If you believe this is an error, please contact KejaPad support.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'landlord_rejected',
            'landlord_id' => $this->landlord->id,
            'reason' => $this->reason,
            'message' => 'Your landlord application was not approved. Reason: '.$this->reason,
        ];
    }
}

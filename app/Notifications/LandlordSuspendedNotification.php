<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandlordSuspendedNotification extends Notification
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
            ->subject('Your KejaPad landlord account has been suspended')
            ->greeting('Hello '.$this->landlord->name.',')
            ->line('Your landlord account has been suspended and access has been restricted.')
            ->line('Reason: '.$this->reason)
            ->line('Please contact KejaPad support if you have questions.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'landlord_suspended',
            'landlord_id' => $this->landlord->id,
            'reason' => $this->reason,
            'message' => 'Your landlord account has been suspended. Reason: '.$this->reason,
        ];
    }
}

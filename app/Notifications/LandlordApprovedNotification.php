<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandlordApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public User $landlord) {}

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
            ->subject('Your KejaPad landlord account has been approved')
            ->greeting('Congratulations, '.$this->landlord->name.'!')
            ->line('Your landlord account has been reviewed and approved.')
            ->line('You can now sign in and start managing your properties.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'landlord_approved',
            'landlord_id' => $this->landlord->id,
            'message' => 'Your landlord account has been approved. You can now access your dashboard.',
        ];
    }
}

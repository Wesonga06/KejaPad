<?php

namespace App\Notifications;

use App\Models\MoveOutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MoveOutStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public MoveOutRequest $moveOut, public string $previousStatus) {}

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
        $mail = (new MailMessage)
            ->subject('Notice to Vacate: '.MoveOutRequest::statusLabel($this->moveOut->status))
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line('Your Notice to Vacate has been updated to: '
                .MoveOutRequest::statusLabel($this->moveOut->status).'.');

        if ($this->moveOut->decision_reason) {
            $mail->line('Reason: '.$this->moveOut->decision_reason);
        }

        if ($this->moveOut->clarification_message) {
            $mail->line('Additional information requested: '.$this->moveOut->clarification_message);
        }

        return $mail->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'move_out_status_changed',
            'move_out_request_id' => $this->moveOut->id,
            'status' => $this->moveOut->status,
            'status_label' => MoveOutRequest::statusLabel($this->moveOut->status),
            'title' => 'Notice to Vacate updated',
            'message' => 'Your Notice to Vacate is now '
                .MoveOutRequest::statusLabel($this->moveOut->status).'.',
            'url' => route('tenant.move-out.show', $this->moveOut),
        ];
    }
}

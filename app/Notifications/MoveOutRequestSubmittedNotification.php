<?php

namespace App\Notifications;

use App\Models\MoveOutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MoveOutRequestSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(public MoveOutRequest $moveOut) {}

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
            ->subject('New Notice to Vacate')
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line($this->moveOut->tenant?->full_name.' has submitted a Notice to Vacate.')
            ->line('Proposed move-out date: '
                .optional($this->moveOut->proposed_move_out_date)->format('d M Y').'.')
            ->line('Unit: '.($this->moveOut->unit?->unit_number ?? '—').'.')
            ->line('Please review the request in KejaPad.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'move_out_request_submitted',
            'move_out_request_id' => $this->moveOut->id,
            'tenant_id' => $this->moveOut->tenant_id,
            'tenant_name' => $this->moveOut->tenant?->full_name,
            'proposed_move_out_date' => optional($this->moveOut->proposed_move_out_date)->toDateString(),
            'title' => 'New Notice to Vacate',
            'message' => $this->moveOut->tenant?->full_name.' submitted a Notice to Vacate.',
            'url' => route('landlord.move-outs.show', $this->moveOut),
        ];
    }
}

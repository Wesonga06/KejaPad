<?php

namespace App\Notifications;

use App\Models\MoveOutInspection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InspectionCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public MoveOutInspection $inspection) {}

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
        $inspection = $this->inspection;

        return (new MailMessage)
            ->subject('Move-out inspection completed')
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line('A move-out inspection has been submitted for unit '
                .($inspection->moveOutRequest?->unit?->unit_number ?? '—').'.')
            ->line('Recommendation: '.$inspection->recommendationLabel().'.')
            ->line('Please review the findings and proceed with clearance and deposit settlement.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'move_out_inspection_completed',
            'inspection_id' => $this->inspection->id,
            'move_out_request_id' => $this->inspection->move_out_request_id,
            'title' => 'Move-out inspection completed',
            'message' => 'Inspection submitted for unit '
                .($this->inspection->moveOutRequest?->unit?->unit_number ?? '—').'.',
            'url' => route('landlord.move-outs.show', $this->inspection->move_out_request_id),
        ];
    }
}

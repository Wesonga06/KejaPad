<?php

namespace App\Notifications;

use App\Models\MoveOutInspection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InspectionAssignedNotification extends Notification
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
        $request = $inspection->moveOutRequest;

        $mail = (new MailMessage)
            ->subject('Move-out inspection assigned')
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line('You have been assigned a move-out inspection.')
            ->line('Unit: '.($request?->unit?->unit_number ?? '—').'.');

        if ($inspection->scheduled_at) {
            $mail->line('Scheduled for: '.$inspection->scheduled_at->format('d M Y H:i').'.');
        }

        return $mail
            ->line('Please complete and submit your findings in KejaPad.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'move_out_inspection_assigned',
            'inspection_id' => $this->inspection->id,
            'move_out_request_id' => $this->inspection->move_out_request_id,
            'title' => 'Move-out inspection assigned',
            'message' => 'A move-out inspection for unit '
                .($this->inspection->moveOutRequest?->unit?->unit_number ?? '—').' has been assigned to you.',
            'url' => route('caretaker.inspections.show', $this->inspection),
        ];
    }
}

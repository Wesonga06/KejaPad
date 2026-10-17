<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the responsible person about a turnover repair task. Contexts:
 *  - assigned:  a caretaker has been given a task;
 *  - resolved:  a caretaker has finished the work and the landlord must verify it;
 *  - verified:  the landlord has confirmed the work, releasing the next stage.
 */
class TurnoverTaskNotification extends Notification
{
    use Queueable;

    public function __construct(public MaintenanceRequest $task, public string $context) {}

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
        return match ($this->context) {
            'assigned' => 'Turnover task assigned',
            'resolved' => 'Turnover repair awaiting verification',
            'verified' => 'Turnover repair verified',
            default => 'Turnover task updated',
        };
    }

    protected function line(): string
    {
        $unit = $this->task->unit?->unit_number ?? '—';

        return match ($this->context) {
            'assigned' => 'You have been assigned "'.$this->task->title.'" for unit '.$unit.'.',
            'resolved' => '"'.$this->task->title.'" for unit '.$unit.' has been resolved and needs your verification.',
            'verified' => '"'.$this->task->title.'" for unit '.$unit.' has been verified as complete.',
            default => '"'.$this->task->title.'" has been updated.',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line($this->line())
            ->when($this->task->due_date, fn (MailMessage $mail) => $mail->line('Due date: '.$this->task->due_date->format('d M Y')))
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'turnover_task',
            'context' => $this->context,
            'maintenance_request_id' => $this->task->id,
            'turnover_id' => $this->task->turnover_id,
            'title' => $this->title(),
            'message' => $this->line(),
            'url' => ($notifiable->isCaretaker() ?? false)
                ? route('caretaker.maintenance.show', $this->task)
                : route('landlord.maintenance.show', $this->task),
        ];
    }
}

<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifies the responsible person about a maintenance task's accountability
 * lifecycle. Contexts:
 *  - assigned:   a caretaker has been given a task;
 *  - submitted:  a caretaker has submitted completion and the landlord must verify;
 *  - verified:   the landlord has confirmed the work is complete;
 *  - returned:   the landlord sent the task back for further work;
 *  - overdue:    a task has passed its deadline (a neutral signal, not a penalty);
 *  - correction: a caretaker has requested a correction to the task record.
 */
class MaintenanceTaskNotification extends Notification
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
            'assigned' => 'Maintenance task assigned',
            'submitted' => 'Maintenance completion awaiting verification',
            'verified' => 'Maintenance work verified',
            'returned' => 'Maintenance task returned for further work',
            'overdue' => 'Maintenance task overdue',
            'correction' => 'Correction requested on a task',
            default => 'Maintenance task updated',
        };
    }

    protected function line(): string
    {
        $unit = $this->task->unit?->unit_number ?? '—';

        return match ($this->context) {
            'assigned' => 'You have been assigned "'.$this->task->title.'" for unit '.$unit.'.',
            'submitted' => '"'.$this->task->title.'" for unit '.$unit.' has been submitted and needs your verification.',
            'verified' => '"'.$this->task->title.'" for unit '.$unit.' has been verified as complete.',
            'returned' => '"'.$this->task->title.'" for unit '.$unit.' was returned for further work.',
            'overdue' => '"'.$this->task->title.'" for unit '.$unit.' is past its deadline.',
            'correction' => 'A correction was requested for "'.$this->task->title.'".',
            default => '"'.$this->task->title.'" has been updated.',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line($this->line())
            ->when($this->task->due_date, fn (MailMessage $mail) => $mail->line('Deadline: '.$this->task->due_date->format('d M Y')))
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'maintenance_task',
            'context' => $this->context,
            'maintenance_request_id' => $this->task->id,
            'title' => $this->title(),
            'message' => $this->line(),
            'url' => ($notifiable->isCaretaker() ?? false)
                ? route('caretaker.maintenance.show', $this->task)
                : route('landlord.maintenance.show', $this->task),
        ];
    }
}

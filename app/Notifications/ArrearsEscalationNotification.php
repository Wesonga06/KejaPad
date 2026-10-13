<?php

namespace App\Notifications;

use App\Models\RentNotice;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArrearsEscalationNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        public Tenant $tenant,
        public array $summary,
        public RentNotice $notice,
    ) {}

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
            ->subject('Arrears escalation: '.$this->tenant->full_name)
            ->greeting('Hello,')
            ->line('Tenant '.$this->tenant->full_name
                .' has arrears of KES '.number_format($this->summary['arrears'], 2).'.')
            ->line('Total outstanding balance: KES '.number_format($this->summary['outstanding'], 2).'.')
            ->line('Days overdue: '.$this->summary['max_overdue_days'].'.')
            ->line('Status: '.$this->summary['status']['label'].'.')
            ->line('Please review the account and, where appropriate, initiate the formal notice process.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'arrears_escalation',
            'notice_id' => $this->notice->id,
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->full_name,
            'outstanding' => $this->summary['outstanding'],
            'arrears' => $this->summary['arrears'],
            'status_key' => $this->summary['status_key'],
            'message' => 'Arrears escalation for '.$this->tenant->full_name,
        ];
    }
}

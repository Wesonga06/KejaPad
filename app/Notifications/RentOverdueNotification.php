<?php

namespace App\Notifications;

use App\Models\RentNotice;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentOverdueNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<int, array{amount: float, date: string, reference: ?string}>  $paymentsSince
     */
    public function __construct(
        public Tenant $tenant,
        public array $summary,
        public RentNotice $notice,
        public array $paymentsSince = [],
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
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello '.$this->tenant->firstName().',')
            ->line('Our records show an outstanding rent balance of KES '
                .number_format($this->summary['outstanding'], 2).' on your account.')
            ->line('Overdue arrears: KES '.number_format($this->summary['arrears'], 2).'.');

        if (! empty($this->paymentsSince)) {
            $mail->line('Payments received since your previous reminder:');

            foreach ($this->paymentsSince as $payment) {
                $mail->line('• KES '.number_format($payment['amount'], 2)
                    .' on '.$payment['date']
                    .($payment['reference'] ? ' ('.$payment['reference'].')' : ''));
            }
        }

        return $mail
            ->line('Please settle the outstanding balance at your earliest convenience.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'rent_overdue',
            'notice_id' => $this->notice->id,
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->full_name,
            'outstanding' => $this->summary['outstanding'],
            'arrears' => $this->summary['arrears'],
            'status_key' => $this->summary['status_key'],
            'stage' => $this->notice->stage,
            'message' => $this->subject(),
        ];
    }

    protected function subject(): string
    {
        return match ($this->notice->type) {
            'follow_up_reminder' => 'Rent arrears follow-up',
            'escalation' => 'Rent arrears escalation',
            default => 'Rent payment reminder',
        };
    }
}

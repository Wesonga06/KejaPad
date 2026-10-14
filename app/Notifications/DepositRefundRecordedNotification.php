<?php

namespace App\Notifications;

use App\Models\DepositTransaction;
use App\Models\SecurityDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositRefundRecordedNotification extends Notification
{
    use Queueable;

    public function __construct(public SecurityDeposit $deposit, public DepositTransaction $transaction) {}

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
            ->subject('Security deposit refund recorded')
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line('A security deposit refund of KES '
                .number_format((float) $this->transaction->amount, 2).' has been recorded.')
            ->line('Net refundable balance remaining: KES '
                .number_format($this->deposit->netRefundable(), 2).'.')
            ->salutation('KejaPad');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'deposit_refund_recorded',
            'security_deposit_id' => $this->deposit->id,
            'deposit_transaction_id' => $this->transaction->id,
            'amount' => (float) $this->transaction->amount,
            'title' => 'Deposit refund recorded',
            'message' => 'A deposit refund of KES '.number_format((float) $this->transaction->amount, 2).' was recorded.',
            'url' => route('tenant.deposit.show'),
        ];
    }
}

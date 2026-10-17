<?php

namespace App\Notifications;

use App\Models\DepositSettlement;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositSettlementNotification extends Notification
{
    public function __construct(public DepositSettlement $settlement, public string $context) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    protected function heading(): string
    {
        return match ($this->context) {
            'sent' => 'Deposit statement ready for your review',
            'responded' => 'Tenant responded to the deposit statement',
            'disputed' => 'Tenant disputed the deposit statement',
            'decision' => 'Deposit settlement decision recorded',
            'settled' => 'Deposit settlement completed',
            default => 'Deposit settlement update',
        };
    }

    protected function line(): string
    {
        $held = number_format((float) $this->settlement->held_amount, 2);

        return match ($this->context) {
            'sent' => 'A settlement statement for the deposit of '.$held.' is ready for you to review and respond to.',
            'responded' => 'The tenant has responded to the deposit statement.',
            'disputed' => 'The tenant has disputed one or more proposed deductions.',
            'decision' => 'A decision has been recorded on the deposit statement.',
            'settled' => 'The deposit settlement has been completed.',
            default => 'The deposit settlement has been updated.',
        };
    }

    protected function url(object $notifiable): ?string
    {
        if (method_exists($notifiable, 'isLandlord') && $notifiable->isLandlord()) {
            return route('landlord.settlements.show', $this->settlement);
        }

        if (method_exists($notifiable, 'isTenant') && $notifiable->isTenant()) {
            return route('tenant.settlement.show', $this->settlement);
        }

        return null;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->heading())
            ->greeting('Hello '.($notifiable->name ?? '').',')
            ->line($this->line())
            ->action('View deposit settlement', $this->url($notifiable) ?? url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'deposit_settlement_'.$this->context,
            'title' => $this->heading(),
            'message' => $this->line(),
            'deposit_settlement_id' => $this->settlement->id,
            'url' => $this->url($notifiable),
        ];
    }
}

<?php

namespace App\Console\Commands;

use App\Services\RentReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendRentReminders extends Command
{
    protected $signature = 'rent:send-reminders {--date= : Run as if this date (Y-m-d)}';

    protected $description = 'Flag overdue tenants and send staged rent reminders / escalations';

    public function handle(RentReminderService $reminders): int
    {
        if (! config('rent.reminders_enabled', true)) {
            $this->warn('Rent reminders are disabled (RENT_REMINDERS_ENABLED=false).');

            return self::SUCCESS;
        }

        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $stats = $reminders->run($date);

        $this->info("Reminders sent: {$stats['reminders']} (escalations: {$stats['escalations']}), skipped: {$stats['skipped']}.");

        return self::SUCCESS;
    }
}

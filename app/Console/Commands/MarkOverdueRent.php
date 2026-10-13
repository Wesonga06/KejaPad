<?php

namespace App\Console\Commands;

use App\Models\RentRecord;
use Illuminate\Console\Command;

class MarkOverdueRent extends Command
{
    protected $signature = 'rent:mark-overdue';

    protected $description = 'Mark unpaid rent records past their due date as overdue';

    public function handle(): int
    {
        $count = RentRecord::whereIn('status', ['pending', 'partial'])
            ->whereDate('due_date', '<', now()->startOfDay())
            ->update(['status' => 'overdue']);

        $this->info("Marked {$count} rent record(s) as overdue.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\MaintenanceAccountabilityService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class EscalateOverdueMaintenance extends Command
{
    protected $signature = 'maintenance:escalate-overdue {--date= : Run as if this date (Y-m-d)}';

    protected $description = 'Flag overdue caretaker tasks so landlords are aware (no penalty is applied)';

    public function handle(MaintenanceAccountabilityService $accountability): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $count = $accountability->escalateOverdue($date);

        $this->info(sprintf('Overdue maintenance tasks escalated: %d.', $count));

        return self::SUCCESS;
    }
}

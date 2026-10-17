<?php

namespace App\Console\Commands;

use App\Services\AlertService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateAlerts extends Command
{
    protected $signature = 'alerts:generate {--date= : Run as if this date (Y-m-d)}';

    protected $description = 'Evaluate early-warning rules and reconcile property alerts';

    public function handle(AlertService $alerts): int
    {
        if (! config('alerts.enabled', true)) {
            $this->warn('Early-warning alerts are disabled (ALERTS_ENABLED=false).');

            return self::SUCCESS;
        }

        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $stats = $alerts->run($date);

        $this->info(sprintf(
            'Alerts — created: %d, refreshed: %d, resolved: %d, notified: %d.',
            $stats['created'],
            $stats['refreshed'],
            $stats['resolved'],
            $stats['notified'],
        ));

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\RentGenerationService;
use Illuminate\Console\Command;

class GenerateMonthlyRent extends Command
{
    protected $signature = 'rent:generate
                            {--month= : Month to generate (defaults to the current month)}
                            {--year= : Year to generate (defaults to the current year)}
                            {--property= : Restrict generation to a single property id}
                            {--force : Run even when automatic generation is disabled}';

    protected $description = 'Generate monthly rent records (due on the configured day) for active tenants and apply held credit';

    public function handle(RentGenerationService $generation): int
    {
        if (! config('rent.auto_generate', true) && ! $this->option('force')) {
            $this->warn('Automatic rent generation is disabled (rent.auto_generate / RENT_AUTO_GENERATE). Use --force to run anyway.');

            return self::SUCCESS;
        }

        $month = (int) ($this->option('month') ?: now()->month);
        $year = (int) ($this->option('year') ?: now()->year);
        $propertyIds = $this->option('property') ? [(int) $this->option('property')] : null;

        $result = $generation->generate($month, $year, $propertyIds);

        $this->info(sprintf(
            'Rent generated for %02d/%d (due %s): %d created, %d skipped, KES %s credit applied.',
            $month,
            $year,
            $result['due_date'],
            $result['created'],
            $result['skipped'],
            number_format($result['credit_applied'], 2),
        ));

        return self::SUCCESS;
    }
}

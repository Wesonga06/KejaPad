<?php

namespace App\Services;

use App\Models\RentRecord;
use App\Models\Tenant;
use Illuminate\Database\UniqueConstraintViolationException;

class RentGenerationService
{
    public function __construct(
        protected RentAllocationService $allocation,
    ) {}

    /**
     * Generate a rent record for every active tenant that does not already have one
     * for the given period, then apply any held credit to the outstanding balance.
     *
     * The due date defaults to the configured day of the month (rent.due_day, i.e. the
     * 3rd) so tenants see the period immediately a new month begins.
     *
     * @param  array<int, int>|null  $propertyIds  Restrict generation to these properties.
     * @return array{created: int, skipped: int, credit_applied: float, due_date: string}
     */
    public function generate(int $month, int $year, ?array $propertyIds = null, ?int $dueDay = null): array
    {
        $dueDay = min(28, max(1, $dueDay ?? (int) config('rent.due_day', 3)));
        $dueDate = sprintf('%04d-%02d-%02d', $year, $month, $dueDay);

        $query = Tenant::where('is_active', true)->with('unit');

        if (! empty($propertyIds)) {
            $query->whereIn('property_id', $propertyIds);
        }

        $created = 0;
        $skipped = 0;
        $creditApplied = 0.0;

        $query->chunkById(100, function ($tenants) use ($month, $year, $dueDate, &$created, &$skipped, &$creditApplied) {
            foreach ($tenants as $tenant) {
                if (! $tenant->unit) {
                    $skipped++;

                    continue;
                }

                $exists = RentRecord::where('tenant_id', $tenant->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->exists();

                if ($exists) {
                    $skipped++;
                } else {
                    try {
                        RentRecord::create([
                            'tenant_id' => $tenant->id,
                            'property_id' => $tenant->property_id,
                            'unit_id' => $tenant->unit_id,
                            'amount_due' => $tenant->unit->rent_amount,
                            'amount_paid' => 0,
                            'due_date' => $dueDate,
                            'status' => 'pending',
                            'month' => $month,
                            'year' => $year,
                        ]);

                        $created++;
                    } catch (UniqueConstraintViolationException) {
                        $skipped++;
                    }
                }

                if (config('rent.apply_credit', true)) {
                    $creditApplied += $this->allocation->applyTenantCredit($tenant);
                }
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'credit_applied' => round($creditApplied, 2),
            'due_date' => $dueDate,
        ];
    }

    /**
     * Generate a single period for one tenant (used when a new tenant is onboarded).
     */
    public function generateForTenant(Tenant $tenant, int $month, int $year, ?int $dueDay = null): ?RentRecord
    {
        if (! $tenant->unit) {
            return null;
        }

        $dueDay = min(28, max(1, $dueDay ?? (int) config('rent.due_day', 3)));
        $dueDate = sprintf('%04d-%02d-%02d', $year, $month, $dueDay);

        $record = RentRecord::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'month' => $month,
                'year' => $year,
            ],
            [
                'property_id' => $tenant->property_id,
                'unit_id' => $tenant->unit_id,
                'amount_due' => $tenant->unit->rent_amount,
                'amount_paid' => 0,
                'due_date' => $dueDate,
                'status' => 'pending',
            ]
        );

        if (config('rent.apply_credit', true)) {
            $this->allocation->applyTenantCredit($tenant);
        }

        return $record;
    }
}

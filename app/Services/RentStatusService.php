<?php

namespace App\Services;

use App\Models\RentRecord;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RentStatusService
{
    /**
     * Per-period ledger breakdown for a tenant, ordered oldest first.
     *
     * @return Collection<int, object>
     */
    public function breakdown(Tenant $tenant): Collection
    {
        return $tenant->rentRecords()
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function (RentRecord $rent) {
                $status = $rent->effectiveStatus();

                return (object) [
                    'id' => $rent->id,
                    'record' => $rent,
                    'period' => $rent->periodLabel(),
                    'charged' => (float) $rent->amount_due,
                    'paid' => (float) $rent->amount_paid,
                    'balance' => $rent->outstanding(),
                    'due_date' => $rent->due_date,
                    'status' => $status,
                    'days_overdue' => $rent->daysOverdue(),
                    'is_overdue' => $status === 'overdue',
                ];
            });
    }

    /**
     * Aggregate rent account summary with a derived colour-coded status.
     *
     * @return array{
     *     charged: float, paid: float, outstanding: float, arrears: float,
     *     credit: float,
     *     max_overdue_days: int, oldest_overdue: ?Carbon,
     *     status: array, status_key: string, periods_overdue: int
     * }
     */
    public function summary(Tenant $tenant): array
    {
        $records = $tenant->rentRecords()->get();

        $charged = round((float) $records->sum(fn (RentRecord $r) => (float) $r->amount_due), 2);
        $paid = round((float) $records->sum(fn (RentRecord $r) => (float) $r->amount_paid), 2);
        $outstanding = round(max(0.0, $charged - $paid), 2);

        $arrears = 0.0;
        $maxOverdueDays = 0;
        $oldestOverdue = null;
        $periodsOverdue = 0;

        foreach ($records as $rent) {
            if (! $rent->isOverdue()) {
                continue;
            }

            $periodsOverdue++;
            $arrears += $rent->outstanding();
            $maxOverdueDays = max($maxOverdueDays, $rent->daysOverdue());

            if ($oldestOverdue === null || $rent->due_date->lt($oldestOverdue)) {
                $oldestOverdue = $rent->due_date;
            }
        }

        $statusKey = $this->statusKey($arrears, $maxOverdueDays);
        $credit = round((float) $tenant->payments()->sum('unallocated_amount'), 2);

        return [
            'charged' => $charged,
            'paid' => $paid,
            'outstanding' => $outstanding,
            'arrears' => round($arrears, 2),
            'credit' => $credit,
            'max_overdue_days' => $maxOverdueDays,
            'oldest_overdue' => $oldestOverdue,
            'periods_overdue' => $periodsOverdue,
            'status_key' => $statusKey,
            'status' => config("rent.statuses.{$statusKey}", config('rent.statuses.green')),
        ];
    }

    /**
     * Derive the colour key from financial data and configurable thresholds.
     */
    public function statusKey(float $arrears, int $overdueDays): string
    {
        $grace = (int) config('rent.grace_period_days', 0);

        if ($arrears <= 0 || $overdueDays <= $grace) {
            return 'green';
        }

        $orangeAfter = (int) config('rent.thresholds.orange_after_days', 14);
        $yellowAfter = (int) config('rent.thresholds.yellow_after_days', 30);

        if ($overdueDays >= $yellowAfter) {
            return 'yellow';
        }

        if ($overdueDays >= $orangeAfter) {
            return 'orange';
        }

        return 'red';
    }

    public function statusFor(Tenant $tenant): array
    {
        return $this->summary($tenant)['status'];
    }
}

<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\RentRecord;
use App\Models\Tenant;
use Carbon\Carbon;

class PaymentBehaviourService
{
    /**
     * Objective payment-behaviour indicators derived from the transaction ledger.
     * These supplement the outstanding balance; they never replace it.
     *
     * @return array<string, mixed>
     */
    public function indicators(Tenant $tenant, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = ($from ?? now()->startOfMonth())->copy()->startOfDay();
        $to = ($to ?? now())->copy()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $records = $tenant->rentRecords()->get();
        $payments = $tenant->payments()->get();

        $outstanding = round($records->sum(fn (RentRecord $r) => $r->outstanding()), 2);
        $totalArrears = round($records->filter(fn (RentRecord $r) => $r->isOverdue())->sum(fn (RentRecord $r) => $r->outstanding()), 2);
        $totalCharged = round($records->sum(fn (RentRecord $r) => (float) $r->amount_due), 2);
        $totalPaid = round($records->sum(fn (RentRecord $r) => (float) $r->amount_paid), 2);

        $paidThisMonth = round(
            $payments
                ->filter(fn (Payment $p) => $p->payment_date && $p->payment_date->isSameMonth(now()))
                ->sum('amount'),
            2
        );

        $periodPayments = $payments->filter(
            fn (Payment $p) => $p->payment_date && $p->payment_date->betweenIncluded($from, $to)
        );

        $paymentsCount = $periodPayments->count();
        $paidInPeriod = round($periodPayments->sum('amount'), 2);
        $months = max(1, (int) $from->diffInMonths($to) + 1);
        $frequency = round($paymentsCount / $months, 2);

        $lastPayment = $payments
            ->filter(fn (Payment $p) => $p->payment_date !== null)
            ->sortByDesc(fn (Payment $p) => $p->payment_date->timestamp)
            ->first();

        // Outstanding owed at the very start of the window (before $from).
        $chargedBefore = $records
            ->filter(fn (RentRecord $r) => $r->due_date && $r->due_date->lt($from))
            ->sum(fn (RentRecord $r) => (float) $r->amount_due);
        $paidBefore = $payments
            ->filter(fn (Payment $p) => $p->payment_date && $p->payment_date->lt($from))
            ->sum('amount');
        $outstandingAtStart = round(max(0.0, (float) $chargedBefore - (float) $paidBefore), 2);

        // Arrears at the start of the window (periods overdue as of $from).
        $arrearsAtStart = round($records
            ->filter(fn (RentRecord $r) => $r->due_date && $r->due_date->lt($from))
            ->sum(fn (RentRecord $r) => $this->outstandingAt($r, $from)), 2);

        $arrearsRecovered = round(max(0.0, $arrearsAtStart - $totalArrears), 2);

        return [
            'outstanding_balance' => $outstanding,
            'total_arrears' => $totalArrears,
            'total_charged' => $totalCharged,
            'total_paid' => $totalPaid,
            'paid_this_month' => $paidThisMonth,
            'paid_in_period' => $paidInPeriod,
            'payments_count' => $paymentsCount,
            'payment_frequency' => $frequency,
            'last_payment_date' => $lastPayment?->payment_date,
            'trend' => $this->trend($outstanding, $outstandingAtStart),
            'outstanding_at_window_start' => $outstandingAtStart,
            'previous_arrears' => $arrearsAtStart,
            'arrears_recovered' => $arrearsRecovered,
            'window_from' => $from,
            'window_to' => $to,
        ];
    }

    /**
     * Outstanding on a single period as of a given date.
     */
    protected function outstandingAt(RentRecord $rent, Carbon $at): float
    {
        $paid = (float) $rent->allocations()
            ->whereHas('payment', fn ($q) => $q->where('payment_date', '<=', $at->toDateString()))
            ->sum('amount');

        return max(0.0, (float) $rent->amount_due - $paid);
    }

    protected function trend(float $current, float $baseline): string
    {
        $epsilon = 1.0;

        if ($current > $baseline + $epsilon) {
            return 'increasing';
        }

        if ($current < $baseline - $epsilon) {
            return 'decreasing';
        }

        return 'unchanged';
    }
}

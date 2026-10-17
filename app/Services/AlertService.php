<?php

namespace App\Services;

use App\Models\DepositSettlement;
use App\Models\MaintenanceRequest;
use App\Models\MoveOutRequest;
use App\Models\Property;
use App\Models\PropertyAlert;
use App\Models\PropertyAlertEvent;
use App\Models\Tenant;
use App\Models\Turnover;
use App\Models\Unit;
use App\Models\UnitInspection;
use App\Models\User;
use App\Notifications\PropertyAlertNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The rule-based early-warning engine.
 *
 * It reads records the platform already holds, derives a set of factual
 * conditions, and reconciles them into the alert store: new conditions raise
 * alerts, unchanged conditions are refreshed, and conditions that are no longer
 * detected are resolved automatically. Nothing here is statistical or
 * predictive — every alert maps to a record a landlord can open and check.
 */
class AlertService
{
    public function __construct(
        protected RentStatusService $rentStatus,
    ) {}

    /**
     * Evaluate every rule and reconcile the alert store. Idempotent: running it
     * twice on the same data raises no duplicate alerts.
     *
     * @return array{created: int, refreshed: int, resolved: int, notified: int}
     */
    public function run(?Carbon $date = null): array
    {
        $metrics = ['created' => 0, 'refreshed' => 0, 'resolved' => 0, 'notified' => 0];

        if (! config('alerts.enabled', true)) {
            return $metrics;
        }

        $date = ($date ?? now())->copy();
        $seen = [];

        Property::query()->chunkById(50, function ($properties) use ($date, &$seen, &$metrics) {
            foreach ($properties as $property) {
                foreach ($this->signalsFor($property, $date) as $key => $signal) {
                    $seen[$key] = true;
                    $this->upsert($signal, $date, $metrics);
                }
            }
        });

        $this->autoResolve(array_keys($seen), $date, $metrics);

        return $metrics;
    }

    /**
     * Whether the condition behind an alert is still detected right now.
     */
    public function conditionStillPresent(PropertyAlert $alert): bool
    {
        if (! $alert->property) {
            return false;
        }

        $signals = $this->signalsFor($alert->property, now()->copy());

        return array_key_exists($alert->condition_key, $signals);
    }

    // ---------------------------------------------------------------------
    // Lifecycle actions
    // ---------------------------------------------------------------------

    public function acknowledge(PropertyAlert $alert, User $user, ?string $note = null): PropertyAlert
    {
        if ($alert->isResolved()) {
            throw ValidationException::withMessages(['alert' => 'This alert has already been resolved.']);
        }

        $alert->forceFill([
            'status' => PropertyAlert::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ])->save();

        $this->record($alert, PropertyAlertEvent::EVENT_ACKNOWLEDGED, $user, $note
            ?? 'Acknowledged by '.$user->name.'.');

        return $alert;
    }

    public function dismiss(PropertyAlert $alert, User $user, string $reason): PropertyAlert
    {
        // A critical warning may never be hidden; it may only be acknowledged.
        if (! $alert->canBeDismissed()) {
            throw ValidationException::withMessages([
                'alert' => 'Critical alerts cannot be dismissed. Acknowledge it once it is being handled.',
            ]);
        }

        $alert->forceFill([
            'status' => PropertyAlert::STATUS_DISMISSED,
            'dismissed_at' => now(),
            'dismissed_by' => $user->id,
            'dismissed_reason' => $note = trim($reason),
        ])->save();

        $this->record($alert, PropertyAlertEvent::EVENT_DISMISSED, $user, $note);

        return $alert;
    }

    /**
     * Resolve an alert, but only once the underlying condition is genuinely gone.
     * Re-evaluates the rule so an alert can never be closed while the issue
     * still exists.
     */
    public function resolve(PropertyAlert $alert, User $user, ?string $note = null): PropertyAlert
    {
        if ($alert->isResolved()) {
            return $alert;
        }

        if ($this->conditionStillPresent($alert)) {
            throw ValidationException::withMessages([
                'alert' => 'The underlying condition is still present, so this alert cannot be resolved yet.',
            ]);
        }

        $this->markResolved($alert, $user, $note ?? 'Resolved by '.$user->name.'.');

        return $alert;
    }

    // ---------------------------------------------------------------------
    // Signal evaluation
    // ---------------------------------------------------------------------

    /**
     * Every condition currently detected for a property, keyed by condition_key.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function signalsFor(Property $property, Carbon $date): array
    {
        $signals = [];

        $this->rentSignals($property, $date, $signals);
        $this->maintenanceSignals($property, $date, $signals);
        $this->recurringMaintenanceSignals($property, $date, $signals);
        $this->inspectionSignals($property, $date, $signals);
        $this->tenancySignals($property, $date, $signals);
        $this->vacancySignals($property, $date, $signals);
        $this->turnoverSignals($property, $date, $signals);
        $this->settlementSignals($property, $date, $signals);

        return $signals;
    }

    protected function rentSignals(Property $property, Carbon $date, array &$signals): void
    {
        $minDays = $this->threshold($property, 'rent.overdue_min_days');
        $criticalDays = $this->threshold($property, 'rent.critical_after_days');
        $repeatedPeriods = $this->threshold($property, 'rent.repeated_periods');
        $windowMonths = $this->threshold($property, 'rent.repeated_window_months', 6);

        $tenants = Tenant::where('property_id', $property->id)
            ->where('is_active', true)
            ->with('rentRecords')
            ->get();

        foreach ($tenants as $tenant) {
            $summary = $this->rentStatus->summary($tenant);

            if ($summary['arrears'] > 0 && $summary['max_overdue_days'] >= $minDays) {
                $oldest = $tenant->rentRecords
                    ->filter(fn ($r) => $r->isOverdue())
                    ->sortBy(fn ($r) => $r->due_date?->timestamp ?? 0)
                    ->first();

                $severity = $summary['max_overdue_days'] >= $criticalDays
                    ? PropertyAlert::SEVERITY_CRITICAL
                    : PropertyAlert::SEVERITY_WARNING;

                $signals["rent_overdue:tenant:{$tenant->id}"] = [
                    'type' => PropertyAlert::TYPE_RENT_OVERDUE,
                    'severity' => $severity,
                    'property_id' => $property->id,
                    'unit_id' => $tenant->unit_id,
                    'tenant_id' => $tenant->id,
                    'subject' => $oldest ?? $tenant,
                    'condition_key' => "rent_overdue:tenant:{$tenant->id}",
                    'title' => 'Rent overdue for '.$tenant->full_name,
                    'reason' => 'KES '.number_format($summary['arrears'], 2).' is owed across '
                        .$summary['periods_overdue'].' period(s); the oldest is '
                        .$summary['max_overdue_days'].' days overdue.',
                    'recommended_action' => 'Review the rent account and issue a reminder or formal notice.',
                    'metadata' => [
                        'arrears' => $summary['arrears'],
                        'max_overdue_days' => $summary['max_overdue_days'],
                        'periods_overdue' => $summary['periods_overdue'],
                        'oldest_overdue' => $summary['oldest_overdue']?->toDateString(),
                    ],
                ];
            }

            $windowStart = $date->copy()->subMonths($windowMonths);

            $repeatedInWindow = $tenant->rentRecords
                ->filter(fn ($r) => $r->isOverdue()
                    && $r->due_date !== null
                    && $r->due_date->greaterThanOrEqualTo($windowStart))
                ->count();

            if ($repeatedPeriods >= 2 && $repeatedInWindow >= $repeatedPeriods) {
                $signals["repeated_arrears:tenant:{$tenant->id}"] = [
                    'type' => PropertyAlert::TYPE_REPEATED_ARREARS,
                    'severity' => PropertyAlert::SEVERITY_WARNING,
                    'property_id' => $property->id,
                    'unit_id' => $tenant->unit_id,
                    'tenant_id' => $tenant->id,
                    'subject' => $tenant,
                    'condition_key' => "repeated_arrears:tenant:{$tenant->id}",
                    'title' => 'Repeated arrears pattern for '.$tenant->full_name,
                    'reason' => $repeatedInWindow.' rent periods have been overdue within the last '
                        .$windowMonths.' months.',
                    'recommended_action' => 'Consider a payment plan or a follow-up conversation with the tenant.',
                    'metadata' => [
                        'periods_overdue_in_window' => $repeatedInWindow,
                        'window_months' => $windowMonths,
                    ],
                ];
            }
        }
    }

    protected function maintenanceSignals(Property $property, Carbon $date, array &$signals): void
    {
        $dueSoon = $this->threshold($property, 'maintenance.due_soon_days');
        $criticalOverdue = $this->threshold($property, 'maintenance.overdue_critical_days');

        $open = MaintenanceRequest::where('property_id', $property->id)
            ->whereNull('turnover_id')
            ->whereIn('status', ['pending', 'assigned', 'in_progress'])
            ->whereNotNull('due_date')
            ->get();

        foreach ($open as $request) {
            $dueIn = $this->daysBetween($date, $request->due_date);

            if ($dueIn < 0) {
                $overdueDays = abs($dueIn);
                $severity = $overdueDays >= $criticalOverdue
                    ? PropertyAlert::SEVERITY_CRITICAL
                    : PropertyAlert::SEVERITY_WARNING;

                $signals["maintenance_overdue:req:{$request->id}"] = [
                    'type' => PropertyAlert::TYPE_MAINTENANCE_OVERDUE,
                    'severity' => $severity,
                    'property_id' => $property->id,
                    'unit_id' => $request->unit_id,
                    'tenant_id' => $request->tenant_id,
                    'subject' => $request,
                    'condition_key' => "maintenance_overdue:req:{$request->id}",
                    'title' => 'Maintenance overdue: '.$request->title,
                    'reason' => 'The task was due on '.$request->due_date->format('d M Y')
                        .' ('.$overdueDays.' days overdue) and is still '.str_replace('_', ' ', $request->status).'.',
                    'recommended_action' => 'Chase the assigned caretaker or reassign the work.',
                    'metadata' => ['overdue_days' => $overdueDays],
                ];
            } elseif ($dueIn <= $dueSoon) {
                $signals["maintenance_due:req:{$request->id}"] = [
                    'type' => PropertyAlert::TYPE_MAINTENANCE_DUE,
                    'severity' => PropertyAlert::SEVERITY_INFO,
                    'property_id' => $property->id,
                    'unit_id' => $request->unit_id,
                    'tenant_id' => $request->tenant_id,
                    'subject' => $request,
                    'condition_key' => "maintenance_due:req:{$request->id}",
                    'title' => 'Maintenance due soon: '.$request->title,
                    'reason' => 'This work is due on '.$request->due_date?->toDateString().'.',
                    'recommended_action' => 'Confirm the assignee is on track to complete it on time.',
                    'metadata' => ['due_in_days' => $dueIn],
                ];
            }
        }
    }

    protected function recurringMaintenanceSignals(Property $property, Carbon $date, array &$signals): void
    {
        $count = $this->threshold($property, 'maintenance.recurring_count');
        $windowDays = $this->threshold($property, 'maintenance.recurring_window_days');

        if ($count < 2 || $windowDays < 1) {
            return;
        }

        $windowStart = $date->copy()->subDays($windowDays);

        $rows = MaintenanceRequest::where('property_id', $property->id)
            ->whereNull('turnover_id')
            ->where('created_at', '>=', $windowStart)
            ->select('unit_id', DB::raw('count(*) as total'))
            ->groupBy('unit_id')
            ->having('total', '>=', $count)
            ->get();

        foreach ($rows as $row) {
            if (! $row->unit_id) {
                continue;
            }

            $unit = Unit::find($row->unit_id);

            if (! $unit) {
                continue;
            }

            $signals["recurring_maintenance:unit:{$unit->id}"] = [
                'type' => PropertyAlert::TYPE_RECURRING_MAINTENANCE,
                'severity' => PropertyAlert::SEVERITY_WARNING,
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'tenant_id' => null,
                'subject' => $unit,
                'condition_key' => "recurring_maintenance:unit:{$unit->id}",
                'title' => 'Recurring maintenance on unit '.$unit->unit_number,
                'reason' => $row->total.' maintenance requests have been raised for this unit in the last '
                    .$windowDays.' days.',
                'recommended_action' => 'Investigate the root cause rather than only repairing each report.',
                'metadata' => ['count' => (int) $row->total, 'window_days' => $windowDays],
            ];
        }
    }

    protected function inspectionSignals(Property $property, Carbon $date, array &$signals): void
    {
        $interval = $this->threshold($property, 'inspection.interval_days');
        $dueSoon = $this->threshold($property, 'inspection.due_soon_days');

        if ($interval < 1) {
            return;
        }

        $units = Unit::where('property_id', $property->id)
            ->where('status', 'occupied')
            ->get();

        foreach ($units as $unit) {
            $last = UnitInspection::where('unit_id', $unit->id)
                ->whereNotNull('inspection_date')
                ->orderByDesc('inspection_date')
                ->orderByDesc('id')
                ->first();

            if (! $last) {
                continue;
            }

            $nextDue = $last->inspection_date->copy()->addDays($interval);
            $dueIn = $this->daysBetween($date, $nextDue);

            if ($dueIn < 0) {
                $overdueDays = abs($dueIn);

                $signals["inspection_overdue:unit:{$unit->id}:{$last->id}"] = [
                    'type' => PropertyAlert::TYPE_INSPECTION_OVERDUE,
                    'severity' => $overdueDays >= $dueSoon
                        ? PropertyAlert::SEVERITY_WARNING
                        : PropertyAlert::SEVERITY_INFO,
                    'property_id' => $property->id,
                    'unit_id' => $unit->id,
                    'tenant_id' => $unit->tenant?->id,
                    'subject' => $last,
                    'condition_key' => "inspection_overdue:unit:{$unit->id}:{$last->id}",
                    'title' => 'Inspection overdue for unit '.$unit->unit_number,
                    'reason' => 'The last inspection was on '.$last->inspection_date->toDateString()
                        .' and the next is due every '.$interval.' days.',
                    'recommended_action' => 'Schedule a routine inspection for this unit.',
                    'metadata' => ['last_inspection' => $last->inspection_date->toDateString(), 'interval_days' => $interval],
                ];
            } elseif ($dueIn <= $dueSoon) {
                $signals["inspection_due:unit:{$unit->id}:{$last->id}"] = [
                    'type' => PropertyAlert::TYPE_INSPECTION_DUE,
                    'severity' => PropertyAlert::SEVERITY_INFO,
                    'property_id' => $property->id,
                    'unit_id' => $unit->id,
                    'tenant_id' => $unit->tenant?->id,
                    'subject' => $last,
                    'condition_key' => "inspection_due:unit:{$unit->id}:{$last->id}",
                    'title' => 'Inspection due soon for unit '.$unit->unit_number,
                    'reason' => 'A routine inspection is due on '.$nextDue->toDateString().'.',
                    'recommended_action' => 'Book a routine inspection before it falls overdue.',
                    'metadata' => ['next_due' => $nextDue->toDateString(), 'interval_days' => $interval],
                ];
            }
        }
    }

    protected function tenancySignals(Property $property, Carbon $date, array &$signals): void
    {
        $endSoon = $this->threshold($property, 'tenancy.end_soon_days');

        $tenants = Tenant::where('property_id', $property->id)
            ->where('is_active', true)
            ->whereNotNull('lease_end_date')
            ->get();

        foreach ($tenants as $tenant) {
            $daysUntil = $this->daysBetween($date, $tenant->lease_end_date);

            if ($daysUntil > $endSoon) {
                continue;
            }

            $expired = $tenant->lease_end_date->lessThan($date);

            $signals["tenancy_ending:tenant:{$tenant->id}"] = [
                'type' => PropertyAlert::TYPE_TENANCY_ENDING,
                'severity' => $expired ? PropertyAlert::SEVERITY_CRITICAL : PropertyAlert::SEVERITY_WARNING,
                'property_id' => $property->id,
                'unit_id' => $tenant->unit_id,
                'tenant_id' => $tenant->id,
                'subject' => $tenant,
                'condition_key' => "tenancy_ending:tenant:{$tenant->id}",
                'title' => $expired
                    ? 'Tenancy term ended for '.$tenant->full_name
                    : 'Tenancy ending soon for '.$tenant->full_name,
                'reason' => $expired
                    ? 'The recorded tenancy end date ('.$tenant->lease_end_date->toDateString().') has passed.'
                    : 'The recorded tenancy end date is '.$tenant->lease_end_date->toDateString().'.',
                'recommended_action' => 'Confirm renewal or begin the move-out process.',
                'metadata' => [
                    'lease_end_date' => $tenant->lease_end_date->toDateString(),
                    'days_until' => $this->daysBetween($date, $tenant->lease_end_date),
                ],
            ];
        }
    }

    protected function vacancySignals(Property $property, Carbon $date, array &$signals): void
    {
        $thresholdDays = $this->threshold($property, 'vacancy.days');
        $criticalDays = $this->threshold($property, 'vacancy.critical_days');

        $units = Unit::where('property_id', $property->id)
            ->where('status', 'vacant')
            ->get();

        foreach ($units as $unit) {
            // A vacant unit with an open turnover is being worked; the turnover
            // rules cover it, so it is not reported as a plain vacancy.
            if (Turnover::where('unit_id', $unit->id)
                ->whereNotIn('status', [Turnover::STATUS_CANCELLED, Turnover::STATUS_OCCUPIED])
                ->exists()) {
                continue;
            }

            $vacatedAt = $this->vacancyStartFor($unit);

            if (! $vacatedAt) {
                continue;
            }

            $daysVacant = $this->daysBetween($vacatedAt, $date);

            if ($daysVacant < $thresholdDays) {
                continue;
            }

            $signals["prolonged_vacancy:unit:{$unit->id}"] = [
                'type' => PropertyAlert::TYPE_PROLONGED_VACANCY,
                'severity' => $daysVacant >= $criticalDays
                    ? PropertyAlert::SEVERITY_CRITICAL
                    : PropertyAlert::SEVERITY_WARNING,
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'tenant_id' => null,
                'subject' => $unit,
                'condition_key' => "prolonged_vacancy:unit:{$unit->id}",
                'title' => 'Unit '.$unit->unit_number.' has been vacant for '.$daysVacant.' days',
                'reason' => 'The unit has been vacant since '.$vacatedAt->toDateString().'.',
                'recommended_action' => 'Review the listing and lettings progress for this unit.',
                'metadata' => ['days_vacant' => $daysVacant, 'vacated_at' => $vacatedAt->toDateString()],
            ];
        }
    }

    protected function turnoverSignals(Property $property, Carbon $date, array &$signals): void
    {
        $stalledDays = $this->threshold($property, 'turnover.stalled_days');
        $criticalDays = $this->threshold($property, 'turnover.critical_days');

        $turnovers = Turnover::where('property_id', $property->id)
            ->whereNotIn('status', [Turnover::STATUS_CANCELLED, Turnover::STATUS_OCCUPIED])
            ->get();

        foreach ($turnovers as $turnover) {
            $since = $turnover->last_activity_at ?? $turnover->created_at;

            if (! $since) {
                continue;
            }

            $daysStalled = $this->daysBetween($since, $date);

            if ($daysStalled < $stalledDays) {
                continue;
            }

            $signals["turnover_stalled:turnover:{$turnover->id}"] = [
                'type' => PropertyAlert::TYPE_TURNOVER_STALLED,
                'severity' => $daysStalled >= $criticalDays
                    ? PropertyAlert::SEVERITY_CRITICAL
                    : PropertyAlert::SEVERITY_WARNING,
                'property_id' => $property->id,
                'unit_id' => $turnover->unit_id,
                'tenant_id' => $turnover->outgoing_tenant_id,
                'subject' => $turnover,
                'condition_key' => "turnover_stalled:turnover:{$turnover->id}",
                'title' => 'Turnover stalled for unit '.($turnover->unit?->unit_number ?? '—'),
                'reason' => 'The turnover has had no activity for '.$daysStalled
                    .' days and is at "'.$turnover->statusLabel().'".',
                'recommended_action' => 'Open the turnover workspace and move the current stage forward.',
                'metadata' => ['days_stalled' => $daysStalled, 'status' => $turnover->status],
            ];
        }
    }

    protected function settlementSignals(Property $property, Carbon $date, array &$signals): void
    {
        $awaitingDays = $this->threshold($property, 'settlement.awaiting_days');
        $criticalDays = $this->threshold($property, 'settlement.critical_days');

        $settlements = DepositSettlement::where('status', '!=', DepositSettlement::STATUS_SETTLED)
            ->whereHas('moveOutRequest', fn ($q) => $q->where('property_id', $property->id))
            ->with('moveOutRequest')
            ->get();

        foreach ($settlements as $settlement) {
            $since = match ($settlement->status) {
                DepositSettlement::STATUS_AWAITING_TENANT => $settlement->sent_at,
                DepositSettlement::STATUS_TENANT_RESPONDED, DepositSettlement::STATUS_DISPUTED => $settlement->responded_at,
                default => $settlement->updated_at,
            } ?? $settlement->updated_at;

            if (! $since) {
                continue;
            }

            $daysWaiting = $this->daysBetween($since, $date);

            if ($daysWaiting < $awaitingDays) {
                continue;
            }

            $awaitingTenant = $settlement->status === DepositSettlement::STATUS_AWAITING_TENANT;

            $signals["settlement_awaiting:settlement:{$settlement->id}"] = [
                'type' => PropertyAlert::TYPE_SETTLEMENT_AWAITING,
                'severity' => $daysWaiting >= $criticalDays
                    ? PropertyAlert::SEVERITY_CRITICAL
                    : PropertyAlert::SEVERITY_WARNING,
                'property_id' => $property->id,
                'unit_id' => $settlement->moveOutRequest?->unit_id,
                'tenant_id' => $settlement->moveOutRequest?->tenant_id,
                'subject' => $settlement,
                'condition_key' => "settlement_awaiting:settlement:{$settlement->id}",
                'title' => $awaitingTenant
                    ? 'Deposit settlement awaiting tenant response'
                    : 'Deposit settlement awaiting your decision',
                'reason' => 'The settlement has been "'.$settlement->statusLabel().'" for '.$daysWaiting.' days.',
                'recommended_action' => $awaitingTenant
                    ? 'Follow up with the tenant regarding the settlement statement.'
                    : 'Record your final decision on the deposit settlement.',
                'metadata' => ['days_waiting' => $daysWaiting, 'status' => $settlement->status],
            ];
        }
    }

    // ---------------------------------------------------------------------
    // Reconciliation
    // ---------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $signal
     * @param  array{created: int, refreshed: int, resolved: int, notified: int}  $metrics
     */
    protected function upsert(array $signal, Carbon $date, array &$metrics): void
    {
        $alert = PropertyAlert::where('condition_key', $signal['condition_key'])
            ->whereIn('status', [
                PropertyAlert::STATUS_ACTIVE,
                PropertyAlert::STATUS_ACKNOWLEDGED,
                PropertyAlert::STATUS_DISMISSED,
            ])
            ->latest('id')
            ->first();

        if (! $alert) {
            $alert = PropertyAlert::create([
                'property_id' => $signal['property_id'],
                'unit_id' => $signal['unit_id'] ?? null,
                'tenant_id' => $signal['tenant_id'] ?? null,
                'type' => $signal['type'],
                'severity' => $signal['severity'],
                'status' => PropertyAlert::STATUS_ACTIVE,
                'condition_key' => $signal['condition_key'],
                'title' => $signal['title'],
                'reason' => $signal['reason'],
                'recommended_action' => $signal['recommended_action'] ?? null,
                'subject_type' => $signal['subject'] instanceof Model
                    ? $signal['subject']->getMorphClass() : null,
                'subject_id' => $signal['subject']?->getKey(),
                'metadata' => $signal['metadata'] ?? null,
                'detected_at' => $date,
                'last_seen_at' => $date,
            ]);

            $this->record($alert, PropertyAlertEvent::EVENT_CREATED, null, 'Alert raised by the early-warning engine.');
            $metrics['created']++;

            if ($this->notify($alert)) {
                $metrics['notified']++;
            }

            return;
        }

        // Keep the factual details current. For a dismissed alert we refresh
        // silently: it stays suppressed (and is auto-resolved once the
        // condition clears, so a recurrence raises a fresh alert).
        $alert->forceFill([
            'title' => $signal['title'],
            'reason' => $signal['reason'],
            'recommended_action' => $signal['recommended_action'] ?? null,
            'severity' => $signal['severity'],
            'metadata' => $signal['metadata'] ?? null,
            'last_seen_at' => now(),
        ])->save();

        $metrics['refreshed']++;

        if ($alert->status === PropertyAlert::STATUS_ACTIVE && $this->reminderDue($alert, $date)) {
            if ($this->notify($alert, $date)) {
                $metrics['notified']++;
            }
        }
    }

    /**
     * @param  array<int, string>  $seenKeys
     * @param  array{created: int, refreshed: int, resolved: int, notified: int}  $metrics
     */
    protected function autoResolve(array $seenKeys, Carbon $date, array &$metrics): void
    {
        PropertyAlert::whereIn('status', [
            PropertyAlert::STATUS_ACTIVE,
            PropertyAlert::STATUS_ACKNOWLEDGED,
            PropertyAlert::STATUS_DISMISSED,
        ])
            ->when($seenKeys !== [], fn ($q) => $q->whereNotIn('condition_key', $seenKeys))
            ->chunkById(200, function ($alerts) use (&$metrics) {
                foreach ($alerts as $alert) {
                    $this->markResolved($alert, null, 'The condition is no longer detected.');
                    $metrics['resolved']++;
                }
            });
    }

    protected function markResolved(PropertyAlert $alert, ?User $user, string $note): void
    {
        $alert->forceFill([
            'status' => PropertyAlert::STATUS_RESOLVED,
            'resolved_at' => now(),
        ])->save();

        $this->record($alert, PropertyAlertEvent::EVENT_RESOLVED, $user, $note);
    }

    protected function reminderDue(PropertyAlert $alert, Carbon $date): bool
    {
        $interval = (int) config('alerts.notifications.reminder_interval_days', 7);

        if (! $alert->notified_at) {
            return true;
        }

        return $this->daysBetween($alert->notified_at, $date) >= $interval;
    }

    protected function notify(PropertyAlert $alert, ?Carbon $date = null): bool
    {
        if (! config('alerts.notifications.enabled', true)) {
            return false;
        }

        $landlord = $alert->property?->landlord;

        if (! $landlord) {
            return false;
        }

        $landlord->notify(new PropertyAlertNotification($alert));

        $alert->forceFill(['notified_at' => ($date ?? now())])->save();

        $this->record($alert, PropertyAlertEvent::EVENT_NOTIFIED, null, 'Landlord notified.');

        return true;
    }

    protected function record(PropertyAlert $alert, string $event, ?User $user, ?string $message): void
    {
        PropertyAlertEvent::create([
            'property_alert_id' => $alert->id,
            'user_id' => $user?->id,
            'event' => $event,
            'message' => $message,
        ]);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    protected function threshold(Property $property, string $key, ?int $default = null): int
    {
        $override = ($property->alert_settings ?? [])[$key] ?? null;

        if ($override !== null && $override !== '') {
            return (int) $override;
        }

        return (int) config("alerts.{$key}", $default ?? 0);
    }

    protected function daysBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) round(
            ($to->copy()->startOfDay()->timestamp - $from->copy()->startOfDay()->timestamp) / 86400
        );
    }

    // The date a vacant unit became vacant, derived from recorded move-out data.
    // Returns null when no move-out has ever been recorded for the unit, so a
    // duration is never invented.
    protected function vacancyStartFor(Unit $unit): ?Carbon
    {
        $tenantOut = Tenant::where('unit_id', $unit->id)
            ->where('is_active', false)
            ->whereNotNull('move_out_date')
            ->max('move_out_date');

        $requestOut = MoveOutRequest::where('unit_id', $unit->id)
            ->whereNotNull('actual_move_out_date')
            ->max('actual_move_out_date');

        $candidates = array_filter([
            $tenantOut ? Carbon::parse($tenantOut) : null,
            $requestOut ? Carbon::parse($requestOut) : null,
        ]);

        if ($candidates === []) {
            return null;
        }

        return collect($candidates)->sortDesc()->first();
    }
}

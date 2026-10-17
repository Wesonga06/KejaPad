<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MoveOutInspection;
use App\Models\MoveOutRequest;
use App\Models\Property;
use App\Models\RentRecord;
use App\Models\SecurityDeposit;
use App\Models\SmsMessage;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SystemReportService
{
    // Coarse platform summary used by the System Reports dashboard.
    public function summary(?Carbon $from, ?Carbon $to): array
    {
        return [
            'users' => $this->users($from, $to),
            'applications' => $this->applications($from, $to),
            'portfolio' => $this->portfolio(),
            'rent' => $this->rent($from, $to),
            'deposits' => $this->deposits(),
            'move_outs' => $this->moveOuts($from, $to),
            'inspections' => $this->inspections(),
            'notifications' => $this->notifications($from, $to),
            'sms' => $this->sms($from, $to),
            'audit' => $this->audit($from, $to),
        ];
    }

    /**
     * @return array{headings: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    public function export(string $report, ?Carbon $from, ?Carbon $to): array
    {
        return match ($report) {
            'users' => $this->usersExport($from, $to),
            'applications' => $this->applicationsExport($from, $to),
            'properties' => $this->propertiesExport(),
            'rent' => $this->rentExport($from, $to),
            'deposits' => $this->depositsExport(),
            'move-outs' => $this->moveOutsExport($from, $to),
            'sms' => $this->smsExport($from, $to),
            'audit' => $this->auditExport($from, $to),
            default => ['headings' => [], 'rows' => []],
        };
    }

    // ---------------------------------------------------------------------
    // Summary sections
    // ---------------------------------------------------------------------

    protected function users(?Carbon $from, ?Carbon $to): array
    {
        $base = $this->between(User::query(), 'created_at', $from, $to);

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
            'by_role' => (clone $base)
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')->pluck('total', 'role')->all(),
            'by_status' => (clone $base)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status')->all(),
        ];
    }

    protected function applications(?Carbon $from, ?Carbon $to): array
    {
        $base = $this->between(User::query()->where('role', 'landlord'), 'created_at', $from, $to);

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', User::STATUS_PENDING)->count(),
            'approved' => (clone $base)->where('status', User::STATUS_APPROVED)->count(),
            'rejected' => (clone $base)->where('status', User::STATUS_REJECTED)->count(),
            'suspended' => (clone $base)->where('status', User::STATUS_SUSPENDED)->count(),
            'actions' => $this->between(DB::table('landlord_approval_actions'), 'created_at', $from, $to)->count(),
        ];
    }

    protected function portfolio(): array
    {
        return [
            'properties' => Property::count(),
            'properties_active' => Property::where('status', 'active')->count(),
            'units' => Unit::count(),
            'units_vacant' => Unit::where('status', 'vacant')->count(),
            'units_occupied' => Unit::where('status', 'occupied')->count(),
            'units_reserved' => Unit::where('status', 'reserved')->count(),
            'tenants' => Tenant::where('is_active', true)->count(),
            'caretakers' => User::where('role', 'caretaker')->where('is_active', true)->count(),
        ];
    }

    protected function rent(?Carbon $from, ?Carbon $to): array
    {
        $base = $this->between(RentRecord::query(), 'due_date', $from, $to);

        $due = (float) (clone $base)->sum('amount_due');
        $paid = (float) (clone $base)->sum('amount_paid');
        $outstanding = (float) (clone $base)->selectRaw(
            'COALESCE(SUM(CASE WHEN amount_paid < amount_due THEN amount_due - amount_paid ELSE 0 END), 0) as total'
        )->value('total');

        return [
            'records' => (clone $base)->count(),
            'due' => $due,
            'collected' => $paid,
            'outstanding' => $outstanding,
            'collection_rate' => $due > 0 ? round(($paid / $due) * 100, 1) : 0.0,
            'by_status' => (clone $base)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status')->all(),
            'overdue_amount' => (float) (clone $base)->where('status', 'overdue')->selectRaw(
                'COALESCE(SUM(CASE WHEN amount_paid < amount_due THEN amount_due - amount_paid ELSE 0 END), 0) as total'
            )->value('total'),
        ];
    }

    protected function deposits(): array
    {
        $refunded = (float) DB::table('deposit_transactions')
            ->where('type', 'refund')->where('status', 'completed')->sum('amount');
        $deducted = (float) DB::table('deposit_transactions')
            ->where('type', 'deduction')->where('status', 'approved')->sum('amount');

        return [
            'total' => SecurityDeposit::count(),
            'held' => (float) SecurityDeposit::sum('received_amount'),
            'required' => (float) SecurityDeposit::sum('required_amount'),
            'verified' => SecurityDeposit::whereNotNull('verified_at')->count(),
            'refunded' => $refunded,
            'deducted' => $deducted,
            'refunded_count' => SecurityDeposit::where('status', SecurityDeposit::STATUS_REFUNDED)->count(),
        ];
    }

    protected function moveOuts(?Carbon $from, ?Carbon $to): array
    {
        $base = $this->between(MoveOutRequest::query(), 'created_at', $from, $to);

        return [
            'total' => (clone $base)->count(),
            'by_status' => (clone $base)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status')->all(),
            'cleared' => (clone $base)->where('clearance_decision', 'cleared')->count(),
            'pending_clearance' => (clone $base)->whereNull('cleared_at')->count(),
        ];
    }

    protected function inspections(): array
    {
        return [
            'total' => MoveOutInspection::count(),
            'by_status' => MoveOutInspection::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status')->all(),
            'completed' => MoveOutInspection::whereNotNull('completed_at')->count(),
        ];
    }

    protected function notifications(?Carbon $from, ?Carbon $to): array
    {
        $base = $this->between(DB::table('notifications'), 'created_at', $from, $to);

        return [
            'total' => (clone $base)->count(),
            'unread' => (clone $base)->whereNull('read_at')->count(),
            'by_type' => (clone $base)
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')->orderByDesc('total')->limit(6)->pluck('total', 'type')->all(),
        ];
    }

    protected function sms(?Carbon $from, ?Carbon $to): array
    {
        $base = $this->between(SmsMessage::query(), 'created_at', $from, $to);

        return [
            'total' => (clone $base)->count(),
            'sent' => (clone $base)->where('status', SmsMessage::STATUS_SENT)->count(),
            'failed' => (clone $base)->where('status', SmsMessage::STATUS_FAILED)->count(),
            'queued' => (clone $base)->where('status', SmsMessage::STATUS_QUEUED)->count(),
        ];
    }

    protected function audit(?Carbon $from, ?Carbon $to): array
    {
        $base = $this->between(AuditLog::query(), 'created_at', $from, $to);

        return [
            'total' => (clone $base)->count(),
            'by_action' => (clone $base)
                ->selectRaw('action, count(*) as total')
                ->groupBy('action')->orderByDesc('total')->limit(6)->pluck('total', 'action')->all(),
            'recent' => (clone $base)->with('user')->latest()->limit(8)->get(),
        ];
    }

    // ---------------------------------------------------------------------
    // CSV exports
    // ---------------------------------------------------------------------

    protected function usersExport(?Carbon $from, ?Carbon $to): array
    {
        $rows = $this->between(User::query(), 'created_at', $from, $to)->latest()->get()
            ->map(fn (User $user) => [
                $user->id, $user->name, $user->email, $user->phone, $user->role,
                $user->statusLabel(), $user->is_active ? 'Active' : 'Inactive',
                $user->created_at?->toDateTimeString(),
            ])->all();

        return ['headings' => ['ID', 'Name', 'Email', 'Phone', 'Role', 'Status', 'Active', 'Registered'], 'rows' => $rows];
    }

    protected function applicationsExport(?Carbon $from, ?Carbon $to): array
    {
        $rows = $this->between(User::query()->where('role', 'landlord'), 'created_at', $from, $to)->latest()->get()
            ->map(fn (User $user) => [
                $user->id, $user->name, $user->email, $user->phone,
                $user->statusLabel(), $user->approved_by, $user->created_at?->toDateTimeString(),
            ])->all();

        return ['headings' => ['ID', 'Name', 'Email', 'Phone', 'Status', 'Reviewed by', 'Applied'], 'rows' => $rows];
    }

    protected function propertiesExport(): array
    {
        $rows = Property::with('landlord')->withCount('units')->get()
            ->map(fn (Property $property) => [
                $property->id, $property->name, $property->location, $property->landlord?->name,
                $property->units_count, $property->status,
            ])->all();

        return ['headings' => ['ID', 'Property', 'Location', 'Landlord', 'Units', 'Status'], 'rows' => $rows];
    }

    protected function rentExport(?Carbon $from, ?Carbon $to): array
    {
        $rows = $this->between(RentRecord::query()->with('tenant'), 'due_date', $from, $to)
            ->orderBy('due_date')->get()
            ->map(fn (RentRecord $record) => [
                $record->id,
                $record->tenant?->full_name,
                $record->unit_id,
                $record->month.'/'.$record->year,
                number_format((float) $record->amount_due, 2, '.', ''),
                number_format((float) $record->amount_paid, 2, '.', ''),
                number_format(max(0, (float) $record->amount_due - (float) $record->amount_paid), 2, '.', ''),
                $record->status,
                $record->due_date?->toDateString(),
            ])->all();

        return ['headings' => ['ID', 'Tenant', 'Unit', 'Period', 'Due', 'Paid', 'Balance', 'Status', 'Due date'], 'rows' => $rows];
    }

    protected function depositsExport(): array
    {
        $rows = SecurityDeposit::with('tenant')->get()
            ->map(fn (SecurityDeposit $deposit) => [
                $deposit->id,
                $deposit->tenant?->full_name,
                number_format((float) $deposit->required_amount, 2, '.', ''),
                number_format((float) $deposit->received_amount, 2, '.', ''),
                number_format($deposit->completedRefunds(), 2, '.', ''),
                number_format($deposit->netRefundable(), 2, '.', ''),
                $deposit->statusLabel(),
            ])->all();

        return ['headings' => ['ID', 'Tenant', 'Required', 'Received', 'Refunded', 'Net refundable', 'Status'], 'rows' => $rows];
    }

    protected function moveOutsExport(?Carbon $from, ?Carbon $to): array
    {
        $rows = $this->between(MoveOutRequest::query()->with('tenant'), 'created_at', $from, $to)->latest()->get()
            ->map(fn (MoveOutRequest $request) => [
                $request->id,
                $request->tenant?->full_name,
                $request->status,
                $request->proposed_move_out_date?->toDateString(),
                $request->actual_move_out_date?->toDateString(),
                $request->clearance_decision,
                $request->created_at?->toDateTimeString(),
            ])->all();

        return ['headings' => ['ID', 'Tenant', 'Status', 'Proposed', 'Actual', 'Clearance', 'Submitted'], 'rows' => $rows];
    }

    protected function smsExport(?Carbon $from, ?Carbon $to): array
    {
        $rows = $this->between(SmsMessage::query(), 'created_at', $from, $to)->latest()->limit(5000)->get()
            ->map(fn (SmsMessage $message) => [
                $message->id, $message->recipient, $message->template, $message->provider,
                $message->status, $message->error, $message->created_at?->toDateTimeString(),
            ])->all();

        return ['headings' => ['ID', 'Recipient', 'Template', 'Provider', 'Status', 'Error', 'Created'], 'rows' => $rows];
    }

    protected function auditExport(?Carbon $from, ?Carbon $to): array
    {
        $rows = $this->between(AuditLog::query()->with('user'), 'created_at', $from, $to)->latest()->limit(5000)->get()
            ->map(fn (AuditLog $log) => [
                $log->id, $log->user?->name, $log->action, $log->description,
                $log->ip_address, $log->created_at?->toDateTimeString(),
            ])->all();

        return ['headings' => ['ID', 'Actor', 'Action', 'Description', 'IP', 'When'], 'rows' => $rows];
    }

    // Apply an inclusive date range to a query builder when bounds are given.
    protected function between($query, string $column, ?Carbon $from, ?Carbon $to)
    {
        return $query
            ->when($from !== null, fn ($q) => $q->where($column, '>=', $from->startOfDay()))
            ->when($to !== null, fn ($q) => $q->where($column, '<=', $to->endOfDay()));
    }
}

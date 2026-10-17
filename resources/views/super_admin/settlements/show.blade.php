@extends('layouts.app')

@section('page-title', 'Deposit Settlement')

@section('content')
    @php
        $moveOut = $settlement->moveOutRequest;
        $tenant = $moveOut?->tenant;
        $approvedDeductions = $deposit?->approvedDeductions() ?? 0;
        $deductionLedger = $deposit
            ? $deposit->transactions->where('type', \App\Models\DepositTransaction::TYPE_DEDUCTION)
            : collect();
    @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Deposit Settlement — {{ $tenant->full_name ?? '—' }}</h1>
            <p class="kp-page-subtitle">
                {{ $moveOut?->property->name ?? '—' }} / {{ $moveOut?->unit->unit_number ?? '—' }}
                &middot; <span class="kp-badge {{ $settlement->statusBadgeClass() }}">{{ $settlement->statusLabel() }}</span>
                <span class="text-muted">(read-only oversight)</span>
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.settlements.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-safe"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) $settlement->held_amount, 2) }}</div>
                <p class="kp-kpi-label">Deposit held</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-dash-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) $approvedDeductions, 2) }}</div>
                <p class="kp-kpi-label">Approved deductions</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-arrow-return-left"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) ($settlement->approved_refund ?? $settlement->proposed_refund), 2) }}</div>
                <p class="kp-kpi-label">Refund</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check2-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) $settlement->refunded_amount, 2) }}</div>
                <p class="kp-kpi-label">Refunded</p>
            </div>
        </div>
    </div>

    @if($comparison && ! $comparison['has_baseline'])
        <div class="alert alert-warning" style="font-size:0.86rem;">
            <i class="bi bi-exclamation-triangle me-1"></i> No move-in baseline exists for this tenancy.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Move-in vs move-out evidence</h2></div>
                @if(! $comparison || empty($comparison['rows']))
                    <p class="text-muted mb-0" style="font-size:0.86rem;">No inspection items available.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr><th>Item</th><th>Move-in</th><th>Move-out</th><th>Classification</th></tr>
                            </thead>
                            <tbody>
                                @foreach($comparison['rows'] as $row)
                                    <tr>
                                        <td class="fw-semibold" style="font-size:0.85rem;">{{ $row['label'] }}</td>
                                        <td style="font-size:0.85rem;">{{ $row['missing_baseline'] ? 'No baseline' : $row['move_in_label'] }}</td>
                                        <td style="font-size:0.85rem;">{{ $row['move_out_label'] }}</td>
                                        <td style="font-size:0.85rem;">
                                            @if($row['nature'])
                                                <span class="kp-badge {{ $row['nature'] === 'new_damage' ? 'kp-badge-danger' : ($row['nature'] === 'wear_tear' ? 'kp-badge-warning' : 'kp-badge-muted') }}">{{ $row['nature_label'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="kp-card">
                <div class="kp-card-header"><h2 class="kp-card-title">Deduction ledger</h2></div>
                @if($deductionLedger->isEmpty())
                    <p class="text-muted mb-0" style="font-size:0.86rem;">No deductions recorded.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Reason</th><th class="text-end">Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($deductionLedger as $transaction)
                                    <tr>
                                        <td style="font-size:0.85rem;">{{ $transaction->reason ?: '—' }}</td>
                                        <td class="text-end">KES {{ number_format((float) $transaction->amount, 2) }}</td>
                                        <td><span class="kp-badge {{ $transaction->statusBadgeClass() }}">{{ $transaction->statusLabel() }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-5">
            @if($settlement->tenant_comment)
                <div class="kp-card mb-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Tenant response</h2></div>
                    <p class="mb-1" style="font-size:0.9rem;">{{ $settlement->tenant_comment }}</p>
                    <div class="text-muted small">{{ optional($settlement->responded_at)->format('d M Y H:i') }}</div>
                </div>
            @endif

            <div class="kp-card">
                <div class="kp-card-header"><h2 class="kp-card-title">History</h2></div>
                @if($settlement->events->isEmpty())
                    <p class="text-muted mb-0" style="font-size:0.85rem;">No history yet.</p>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach($settlement->events as $event)
                            <li class="d-flex gap-3 mb-3">
                                <div class="text-muted" style="font-size:0.75rem;min-width:110px;">
                                    {{ optional($event->created_at)->format('d M Y H:i') }}
                                </div>
                                <div>
                                    <div style="font-size:0.86rem;" class="fw-semibold">{{ $event->label() }}</div>
                                    @if($event->message)
                                        <div class="text-muted" style="font-size:0.82rem;">{{ $event->message }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size:0.74rem;">
                                        {{ $event->user->name ?? 'System' }}{{ $event->role ? ' ('.str_replace('_', ' ', $event->role).')' : '' }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection

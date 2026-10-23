@extends('layouts.app')

@section('page-title', 'My Deposit')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">My Security Deposit</h1>
            <p class="kp-page-subtitle">
                {{ $tenant->property->name ?? '—' }} / {{ $tenant->unit->unit_number ?? '—' }}
                &middot; <span class="kp-badge {{ $deposit->statusBadgeClass() }}">{{ $deposit->statusLabel() }}</span>
            </p>
        </div>
    </div>

    @if(! $deposit->isSatisfied())
        <div class="alert alert-warning" style="font-size:0.88rem;">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Your deposit is not fully paid. Outstanding: <strong>KES {{ number_format($deposit->outstanding(), 2) }}</strong>.
        </div>
    @elseif(! $deposit->isVerified())
        <div class="alert alert-info" style="font-size:0.88rem;">
            <i class="bi bi-hourglass-split me-1"></i>
            Your deposit has been received and is awaiting verification by your landlord.
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-cash-stack"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format((float) $deposit->required_amount, 2) }}</div>
                <p class="kp-kpi-label">Required</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check2-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format((float) $deposit->received_amount, 2) }}</div>
                <p class="kp-kpi-label">Received</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-dash-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($deposit->approvedDeductions(), 2) }}</div>
                <p class="kp-kpi-label">Approved Deductions</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-arrow-return-left"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($deposit->availableForRefund(), 2) }}</div>
                <p class="kp-kpi-label">Available to Refund</p>
                <p class="text-muted small mb-0">After approved deductions and pending refunds.</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Deposit transactions</h2>
            @if($deposit->isVerified())
                <span class="text-muted small">Verified {{ optional($deposit->verified_at)->format('d M Y') }}</span>
            @endif
        </div>
        @if($deposit->transactions->isEmpty())
            <div class="kp-empty"><i class="bi bi-journal-text"></i><p class="mb-0">No deposit transactions yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Date</th><th>Type</th><th class="text-end">Amount</th><th>Status</th><th>Details</th></tr>
                    </thead>
                    <tbody>
                        @foreach($deposit->transactions->sortByDesc('id') as $tx)
                            <tr>
                                <td class="text-muted small">{{ $tx->created_at->format('d M Y') }}</td>
                                <td><span class="kp-badge {{ $tx->typeBadgeClass() }}">{{ $tx->typeLabel() }}</span></td>
                                <td class="text-end fw-semibold">KES {{ number_format((float) $tx->amount, 2) }}</td>
                                <td><span class="kp-badge {{ $tx->statusBadgeClass() }}">{{ $tx->statusLabel() }}</span></td>
                                <td style="font-size:0.85rem;">
                                    @if($tx->reference)<div>Ref: {{ $tx->reference }}</div>@endif
                                    @if($tx->reason)<div>{{ $tx->reason }}</div>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

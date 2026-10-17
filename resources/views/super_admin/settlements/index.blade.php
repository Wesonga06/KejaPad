@extends('layouts.app')

@section('page-title', 'Deposit Settlements')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Deposit Settlement Oversight</h1>
            <p class="kp-page-subtitle">Platform-wide, read-only view of deposit settlements between landlords and tenants.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-file-earmark-text"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['total'] }}</div>
                <p class="kp-kpi-label">Total settlements</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon red"><i class="bi bi-exclamation-circle"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['disputed'] }}</div>
                <p class="kp-kpi-label">Disputed</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check2-circle"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['settled'] }}</div>
                <p class="kp-kpi-label">Settled</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-safe"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format($stats['refunded'], 2) }}</div>
                <p class="kp-kpi-label">Total refunded</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Settlements</h2>
            <form method="GET" action="{{ route('super_admin.settlements.index') }}" class="d-flex gap-2 flex-wrap">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                       placeholder="Search tenant..." style="max-width:200px;">
                <select name="status" class="form-select form-select-sm" style="max-width:220px;" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach(\App\Models\DepositSettlement::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>

        @if($settlements->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-file-earmark-text"></i>
                <p class="mb-0">No deposit settlements found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th class="text-end">Held</th>
                            <th class="text-end">Refunded</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($settlements as $settlement)
                            <tr>
                                <td class="fw-semibold">{{ $settlement->moveOutRequest->tenant->full_name ?? '—' }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $settlement->moveOutRequest->property->name ?? '—' }}
                                    <span class="text-muted">/ {{ $settlement->moveOutRequest->unit->unit_number ?? '—' }}</span>
                                </td>
                                <td class="text-end">KES {{ number_format((float) $settlement->held_amount, 2) }}</td>
                                <td class="text-end">KES {{ number_format((float) $settlement->refunded_amount, 2) }}</td>
                                <td><span class="kp-badge {{ $settlement->statusBadgeClass() }}">{{ $settlement->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('super_admin.settlements.show', $settlement) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $settlements->links() }}</div>
        @endif
    </div>
@endsection

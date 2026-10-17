@extends('layouts.app')

@section('page-title', 'Deposit Settlements')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Deposit Settlements</h1>
            <p class="kp-page-subtitle">Transparent move-out deposit statements: proposed deductions, the tenant's response and the final reviewed refund.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-file-earmark-text"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['draft'] }}</div>
                <p class="kp-kpi-label">Preparing</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-hourglass-split"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['awaiting_tenant'] }}</div>
                <p class="kp-kpi-label">Awaiting Tenant</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-patch-check"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['approved'] }}</div>
                <p class="kp-kpi-label">Approved</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check2-circle"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['settled'] }}</div>
                <p class="kp-kpi-label">Settled</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Settlements</h2>
            <form method="GET" action="{{ route('landlord.settlements.index') }}" class="d-flex gap-2 flex-wrap">
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
                <p class="mb-0">No deposit settlements yet. Start one from a move-out request after the inspection is submitted.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th class="text-end">Held</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Refund</th>
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
                                <td class="text-end">KES {{ number_format((float) $settlement->proposed_deductions, 2) }}</td>
                                <td class="text-end">KES {{ number_format((float) ($settlement->approved_refund ?? $settlement->proposed_refund), 2) }}</td>
                                <td><span class="kp-badge {{ $settlement->statusBadgeClass() }}">{{ $settlement->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('landlord.settlements.show', $settlement) }}" class="btn btn-sm btn-outline-primary">Open</a>
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

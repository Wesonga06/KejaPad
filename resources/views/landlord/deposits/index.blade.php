@extends('layouts.app')

@section('page-title', 'Security Deposits')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Security Deposits</h1>
            <p class="kp-page-subtitle">Deposits held for tenancies, with receipts, deductions and refunds tracked separately from rent.</p>
        </div>
        <div class="kp-page-actions">
            <form method="POST" action="{{ route('landlord.deposits.sync') }}"
                  onsubmit="return confirm('Create deposit records for any active tenants that do not have one?');">
                @csrf
                <button class="btn btn-outline-primary">
                    <i class="bi bi-arrow-repeat me-2"></i> Generate Deposit Records
                </button>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-shield-check"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($stats['held'], 0) }}</div>
                <p class="kp-kpi-label">Total Received</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-hourglass-split"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($stats['outstanding'], 0) }}</div>
                <p class="kp-kpi-label">Outstanding</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-patch-check"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['verified'] }}</div>
                <p class="kp-kpi-label">Verified</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-arrow-return-left"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['refunded'] }}</div>
                <p class="kp-kpi-label">Refunded</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Deposits</h2>
            <form method="GET" action="{{ route('landlord.deposits.index') }}" class="d-flex gap-2 flex-wrap">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                       placeholder="Search tenant..." style="max-width:200px;">
                <select name="property_id" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                    <option value="">All properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected(request('property_id') == $property->id)>{{ $property->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach([
                        'pending' => 'Pending',
                        'partial' => 'Partially paid',
                        'received' => 'Received',
                        'verified' => 'Verified',
                        'partially_refunded' => 'Partially refunded',
                        'refunded' => 'Refunded',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>

        @if($deposits->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-shield-check"></i>
                <p class="mb-0">No deposits found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th class="text-end">Required</th>
                            <th class="text-end">Received</th>
                            <th class="text-end">Outstanding</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deposits as $deposit)
                            <tr>
                                <td class="fw-semibold">{{ $deposit->tenant->full_name ?? '—' }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $deposit->property->name ?? '—' }}
                                    <span class="text-muted">/ {{ $deposit->unit->unit_number ?? '—' }}</span>
                                </td>
                                <td class="text-end">KES {{ number_format((float) $deposit->required_amount, 2) }}</td>
                                <td class="text-end">KES {{ number_format((float) $deposit->received_amount, 2) }}</td>
                                <td class="text-end {{ $deposit->outstanding() > 0 ? 'text-danger fw-semibold' : '' }}">
                                    KES {{ number_format($deposit->outstanding(), 2) }}
                                </td>
                                <td><span class="kp-badge {{ $deposit->statusBadgeClass() }}">{{ $deposit->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('landlord.deposits.show', $deposit) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $deposits->links() }}</div>
        @endif
    </div>
@endsection

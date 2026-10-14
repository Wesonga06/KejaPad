@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('content')
    @php
        $barMax = max($totalExpected, $totalCollected, $totalOutstanding, 1);
        $tonnes = fn ($v) => (int) round(($v / $barMax) * 100);
    @endphp

    <!-- Welcome Banner -->
    <div class="kp-card mb-4" style="background: linear-gradient(120deg, var(--kp-deep) 0%, #47218a 100%); border: none;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h2 class="fw-bold mb-1" style="color:#fff; font-size:1.4rem;">
                    Welcome back, {{ auth()->user()->name }} &#128075;
                </h2>
                <p class="mb-0" style="color:rgba(255,255,255,0.7); font-size:0.9rem;">
                    Here's your property overview for {{ date('F Y') }}.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('landlord.properties.create') }}" class="btn btn-gold">
                    <i class="bi bi-plus-circle me-2"></i> Add Property
                </a>
                <a href="{{ route('landlord.rent.index') }}" class="btn btn-outline-light"
                   style="border-color: rgba(255,255,255,0.35); color:#fff;">
                    <i class="bi bi-cash-coin me-2"></i> Manage Rent
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top">
                    <div class="kp-kpi-icon purple"><i class="bi bi-buildings"></i></div>
                </div>
                <div class="kp-kpi-value">{{ $totalProperties }}</div>
                <p class="kp-kpi-label">Properties</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top">
                    <div class="kp-kpi-icon gold"><i class="bi bi-door-open"></i></div>
                    <span class="badge kp-badge-purple">{{ $occupancyRate }}% occupied</span>
                </div>
                <div class="kp-kpi-value">{{ $totalUnits }}</div>
                <p class="kp-kpi-label">Total Units</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top">
                    <div class="kp-kpi-icon green"><i class="bi bi-people"></i></div>
                </div>
                <div class="kp-kpi-value">{{ $totalTenants }}</div>
                <p class="kp-kpi-label">Active Tenants</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top">
                    <div class="kp-kpi-icon red"><i class="bi bi-exclamation-octagon"></i></div>
                </div>
                <div class="kp-kpi-value" style="color:var(--kp-danger);">KES {{ number_format($arrearsTotal, 0) }}</div>
                <p class="kp-kpi-label">Total Arrears</p>
                <a href="{{ route('landlord.arrears.index') }}" class="kp-kpi-trend down">
                    Review arrears <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Rent Collection + Occupancy -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <div>
                        <h2 class="kp-card-title">Rent Collection Overview</h2>
                        <p class="kp-card-subtitle">{{ date('F Y') }}</p>
                    </div>
                    <span class="badge {{ $collectionRate >= 80 ? 'kp-badge-success' : ($collectionRate >= 50 ? 'kp-badge-warning' : 'kp-badge-danger') }}">
                        {{ $collectionRate }}% collected
                    </span>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="kp-kpi-label mb-1">Expected</div>
                        <div class="fw-bold" style="font-size:1.15rem; color:var(--kp-deep);">
                            KES {{ number_format($totalExpected, 0) }}
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="kp-kpi-label mb-1">Collected</div>
                        <div class="fw-bold" style="font-size:1.15rem; color:var(--kp-success);">
                            KES {{ number_format($totalCollected, 0) }}
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="kp-kpi-label mb-1">Outstanding</div>
                        <div class="fw-bold" style="font-size:1.15rem; color:var(--kp-danger);">
                            KES {{ number_format($totalOutstanding, 0) }}
                        </div>
                    </div>
                </div>

                <div class="kp-progress mb-2">
                    <span style="width:{{ min($collectionRate, 100) }}%; background: linear-gradient(90deg, var(--kp-royal), var(--kp-deep));"></span>
                </div>

                <div class="kp-bars mt-4">
                    <div class="kp-bar-col">
                        <span class="kp-bar-value">KES {{ number_format($totalExpected, 0) }}</span>
                        <div class="kp-bar" style="height:{{ $tonnes($totalExpected) }}%;"></div>
                        <span class="kp-bar-label">Expected</span>
                    </div>
                    <div class="kp-bar-col">
                        <span class="kp-bar-value">KES {{ number_format($totalCollected, 0) }}</span>
                        <div class="kp-bar gold" style="height:{{ $tonnes($totalCollected) }}%;"></div>
                        <span class="kp-bar-label">Collected</span>
                    </div>
                    <div class="kp-bar-col">
                        <span class="kp-bar-value">KES {{ number_format($totalOutstanding, 0) }}</span>
                        <div class="kp-bar" style="height:{{ $tonnes($totalOutstanding) }}%; background: linear-gradient(180deg, var(--kp-danger), #b53232);"></div>
                        <span class="kp-bar-label">Outstanding</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Occupancy</h2>
                </div>
                <div class="text-center mb-3">
                    <div class="kp-kpi-value" style="font-size:2.2rem;">{{ $occupancyRate }}%</div>
                    <p class="kp-kpi-label">of units occupied</p>
                </div>
                <div class="kp-progress mb-3">
                    <span style="width:{{ $occupancyRate }}%; background: linear-gradient(90deg, var(--kp-gold), #e0ac1f);"></span>
                </div>
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-bold" style="color:var(--kp-success); font-size:1.15rem;">{{ $occupiedUnits }}</div>
                        <small class="text-muted">Occupied</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold" style="color:var(--kp-muted); font-size:1.15rem;">{{ $vacantUnits }}</div>
                        <small class="text-muted">Vacant</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Arrears Overview -->
    <div class="kp-card mb-4">
        <div class="kp-card-header">
            <div>
                <h2 class="kp-card-title">Arrears Overview</h2>
                <p class="kp-card-subtitle">Tenants by rent status</p>
            </div>
            <a href="{{ route('landlord.arrears.index') }}" class="btn btn-sm btn-outline-danger">
                Review Arrears
            </a>
        </div>
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="p-3 rounded-3" style="background: rgba(22,134,83,0.06);">
                    <div class="fw-bold" style="font-size:1.6rem; color:var(--kp-success);">{{ $statusCounts['green'] }}</div>
                    <small class="text-muted">Up to date</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded-3" style="background: rgba(220,69,69,0.06);">
                    <div class="fw-bold" style="font-size:1.6rem; color:var(--kp-danger);">{{ $statusCounts['red'] }}</div>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded-3" style="background: rgba(233,155,32,0.08);">
                    <div class="fw-bold" style="font-size:1.6rem; color:#b7791f;">{{ $statusCounts['orange'] }}</div>
                    <small class="text-muted">Escalated</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded-3" style="background: rgba(244,197,66,0.14);">
                    <div class="fw-bold" style="font-size:1.6rem; color:#8a6a00;">{{ $statusCounts['yellow'] }}</div>
                    <small class="text-muted">Further action</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Recent Tenants</h2>
                    <a href="{{ route('landlord.tenants.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                @forelse($recentTenants as $tenant)
                    <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--kp-border);">
                        <div class="kp-avatar sm">{{ strtoupper(substr($tenant->full_name, 0, 2)) }}</div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:0.88rem;">{{ $tenant->full_name }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $tenant->property->name }} &bull; {{ $tenant->unit->unit_number }}
                            </div>
                        </div>
                        <a href="{{ route('landlord.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                @empty
                    <div class="text-center text-muted py-4" style="font-size:0.85rem;">No tenants yet.</div>
                @endforelse
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Recent Payments</h2>
                    <a href="{{ route('landlord.rent.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                @forelse($recentPayments as $payment)
                    <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--kp-border);">
                        <div class="kp-kpi-icon green" style="width:38px;height:38px;font-size:1rem;">
                            <i class="bi bi-arrow-down-left"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:0.88rem;">{{ $payment->tenant->full_name ?? 'Tenant' }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $payment->methodLabel() }} &bull;
                                {{ $payment->payment_date?->format('d M Y') ?? $payment->created_at->format('d M Y') }}
                            </div>
                        </div>
                        <div class="fw-bold" style="font-size:0.88rem; color:var(--kp-success);">
                            +{{ number_format($payment->amount, 0) }}
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4" style="font-size:0.85rem;">No payments recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Quick Actions</h2>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('landlord.properties.create') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Property
            </a>
            <a href="{{ route('landlord.tenants.create') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-person-plus me-1"></i> Add Tenant
            </a>
            <a href="{{ route('landlord.rent.index') }}" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-cash-coin me-1"></i> Manage Rent
            </a>
            <a href="{{ route('landlord.notices.create') }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-envelope-paper me-1"></i> Record Notice
            </a>
        </div>
    </div>
@endsection

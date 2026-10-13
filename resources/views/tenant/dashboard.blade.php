@extends('layouts.app')

@section('page-title', 'My Dashboard')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('tenant.dashboard') }}" class="nav-link active">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('tenant.announcements.index') }}" class="nav-link">
    <i class="bi bi-megaphone"></i> Announcements
    </a>

    <div class="nav-section-title">My Account</div>
    <a href="{{ route('tenant.payments') }}" class="nav-link">
        <i class="bi bi-receipt"></i> Payment History
    </a>
    <a href="{{ route('tenant.maintenance.index') }}" class="nav-link">
    <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('tenant.profile') }}" class="nav-link">
        <i class="bi bi-person-circle"></i> My Profile
    </a>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="p-4 mb-4 rounded-3"
         style="background: linear-gradient(135deg, #4c1d95, #d4af37); color: white;">
        <h4 class="fw-bold mb-1">Welcome, {{ auth()->user()->name }} 👋</h4>
        <p class="mb-0 opacity-75">
            {{ $tenant->property->name }} —
            Unit {{ $tenant->unit->unit_number }}
        </p>
    </div>

    <!-- Rent Status Banner -->
    @if($currentRent && $currentRent->status === 'paid')
        <div class="alert alert-success d-flex align-items-center gap-3">
            <i class="bi bi-check-circle-fill fs-4"></i>
            <div>
                <strong>Rent fully paid for {{ date('F Y') }}.</strong>
                <div class="small">Thank you! Your payment has been received.</div>
            </div>
        </div>
    @elseif($currentRent && $currentRent->status === 'overdue')
        <div class="alert alert-danger d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div>
                    <strong>Rent overdue for {{ date('F Y') }}.</strong>
                    <div class="small">
                        Outstanding balance: KES {{ number_format($currentRent->balance(), 2) }}.
                        Please pay as soon as possible.
                    </div>
                </div>
            </div>
            <a href="{{ route('tenant.pay', $currentRent) }}" class="btn btn-danger">
                <i class="bi bi-phone me-2"></i> Pay via M-Pesa
            </a>
        </div>
    @elseif($currentRent)
        <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-hourglass-split fs-4"></i>
                <div>
                    <strong>{{ ucfirst($currentRent->status) }} — {{ date('F Y') }}.</strong>
                    <div class="small">
                        Outstanding balance: KES {{ number_format($currentRent->balance(), 2) }}.
                    </div>
                </div>
            </div>
            <a href="{{ route('tenant.pay', $currentRent) }}" class="btn btn-primary">
                <i class="bi bi-phone me-2"></i> Pay via M-Pesa
            </a>
        </div>
    @endif

    <!-- Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Monthly Rent</p>
                        <h3 class="stat-value" style="font-size:1.3rem;">
                            KES {{ number_format($tenant->unit->rent_amount, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#efe7fb;">
                        <i class="bi bi-cash-coin" style="color:#4c1d95;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Outstanding Balance</p>
                        <h3 class="stat-value" style="font-size:1.3rem;
                            {{ $totalOutstanding > 0 ? 'color:#dc3545;' : 'color:#198754;' }}">
                            KES {{ number_format($totalOutstanding, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#fbf3d5;">
                        <i class="bi bi-exclamation-circle" style="color:#b8860b;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">This Month Status</p>
                        <h3 class="stat-value" style="font-size:1rem; margin-top:6px;">
                            @if($currentRent)
                                <span class="badge rounded-pill
                                    @if($currentRent->status === 'paid') bg-success
                                    @elseif($currentRent->status === 'partial') bg-warning text-dark
                                    @elseif($currentRent->status === 'overdue') bg-danger
                                    @else bg-secondary
                                    @endif" style="font-size:0.85rem;">
                                    {{ ucfirst($currentRent->status) }}
                                </span>
                            @else
                                <span class="text-muted" style="font-size:0.85rem;">No record</span>
                            @endif
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#e6f9f0;">
                        <i class="bi bi-calendar-check" style="color:#198754;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Due Date</p>
                        <h3 class="stat-value" style="font-size:1rem; margin-top:6px;">
                            @if($currentRent)
                                {{ $currentRent->due_date->format('d M Y') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#fff3e0;">
                        <i class="bi bi-calendar-event" style="color:#fd7e14;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- This Month Rent Status -->
    @if($currentRent)
    <div class="stat-card mb-4">
        <h6 class="fw-bold mb-3 pb-2 border-bottom">
            This Month — {{ date('F Y') }}
        </h6>
        <div class="row g-3">
            <div class="col-sm-3">
                <small class="text-muted d-block">Amount Due</small>
                <span class="fw-semibold">
                    KES {{ number_format($currentRent->amount_due, 2) }}
                </span>
            </div>
            <div class="col-sm-3">
                <small class="text-muted d-block">Amount Paid</small>
                <span class="fw-semibold text-success">
                    KES {{ number_format($currentRent->amount_paid, 2) }}
                </span>
            </div>
            <div class="col-sm-3">
                <small class="text-muted d-block">Balance</small>
                <span class="fw-semibold
                    {{ $currentRent->balance() > 0 ? 'text-danger' : 'text-success' }}">
                    KES {{ number_format($currentRent->balance(), 2) }}
                </span>
            </div>
            <div class="col-sm-3">
                <small class="text-muted d-block">Transaction</small>
                <span class="fw-semibold">
                    {{ $currentRent->transaction_code ?? '—' }}
                </span>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-warning">
        <i class="bi bi-info-circle me-2"></i>
        No rent record has been generated for {{ date('F Y') }} yet.
        Please contact your landlord.
    </div>
    @endif

    <!-- Rent Status & Payment Behaviour -->
    @php
        $trendMeta = [
            'increasing' => ['label' => 'Increasing', 'badge' => 'danger', 'icon' => 'arrow-up-right'],
            'decreasing' => ['label' => 'Decreasing', 'badge' => 'success', 'icon' => 'arrow-down-right'],
            'unchanged'  => ['label' => 'Unchanged', 'badge' => 'secondary', 'icon' => 'arrow-right'],
        ][$behaviour['trend']] ?? ['label' => 'Unchanged', 'badge' => 'secondary', 'icon' => 'arrow-right'];
    @endphp
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">My Rent Status</h6>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted">Status</span>
                    <span class="badge rounded-pill px-3 py-2"
                          style="background:{{ $rentStatus['status']['color'] }}; font-size:0.85rem;">
                        {{ $rentStatus['status']['label'] }}
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total charged</span>
                    <span class="fw-semibold">KES {{ number_format($rentStatus['charged'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total paid</span>
                    <span class="fw-semibold text-success">KES {{ number_format($rentStatus['paid'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Outstanding</span>
                    <span class="fw-semibold {{ $rentStatus['outstanding'] > 0 ? 'text-danger' : 'text-success' }}">
                        KES {{ number_format($rentStatus['outstanding'], 2) }}
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Arrears</span>
                    <span class="fw-semibold {{ $rentStatus['arrears'] > 0 ? 'text-danger' : 'text-success' }}">
                        KES {{ number_format($rentStatus['arrears'], 2) }}
                        @if($rentStatus['max_overdue_days'] > 0)
                            <small>({{ $rentStatus['max_overdue_days'] }} days)</small>
                        @endif
                    </span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Credit balance</span>
                    <span class="fw-semibold {{ $rentStatus['credit'] > 0 ? 'text-success' : 'text-muted' }}">
                        KES {{ number_format($rentStatus['credit'], 2) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Payment Activity</h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Paid this month</span>
                    <span class="fw-semibold">KES {{ number_format($behaviour['paid_this_month'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Payments (6 months)</span>
                    <span class="fw-semibold">{{ $behaviour['payments_count'] }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Last payment</span>
                    <span class="fw-semibold">{{ $behaviour['last_payment_date']?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Balance trend</span>
                    <span class="badge bg-{{ $trendMeta['badge'] }}">
                        <i class="bi bi-{{ $trendMeta['icon'] }} me-1"></i>{{ $trendMeta['label'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="stat-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0">Recent Payments</h6>
            <a href="{{ route('tenant.payments') }}"
               class="btn btn-sm btn-outline-primary">View All</a>
        </div>

        @if($recentPayments->isEmpty())
            <div class="text-center py-4">
                <i class="bi bi-receipt"
                   style="font-size:2rem; color:#dee2e6;"></i>
                <p class="mt-2 text-muted mb-0">No payments recorded yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Amount Due</th>
                            <th>Amount Paid</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment->monthName() }} {{ $payment->year }}</td>
                            <td>KES {{ number_format($payment->amount_due, 2) }}</td>
                            <td>KES {{ number_format($payment->amount_paid, 2) }}</td>
                            <td>
                                {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td>
                                <span class="badge rounded-pill
                                    @if($payment->status === 'paid') bg-success
                                    @elseif($payment->status === 'partial') bg-warning text-dark
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
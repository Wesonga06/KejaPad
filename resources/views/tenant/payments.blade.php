@extends('layouts.app')

@section('page-title', 'Payment History')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('tenant.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">My Account</div>
    <a href="{{ route('tenant.payments') }}" class="nav-link active">
        <i class="bi bi-receipt"></i> Payment History
    </a>
    <a href="{{ route('tenant.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('tenant.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('tenant.profile') }}" class="nav-link">
        <i class="bi bi-person-circle"></i> My Profile
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Payment History</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                All rent records for
                {{ $tenant->property->name }} —
                Unit {{ $tenant->unit->unit_number }}
            </p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-primary mb-0">
                    {{ $payments->count() }}
                </h4>
                <small class="text-muted">Total Records</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success mb-0">
                    {{ $payments->where('status', 'paid')->count() }}
                </h4>
                <small class="text-muted">Paid</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning mb-0">
                    {{ $payments->whereIn('status', ['pending', 'partial'])->count() }}
                </h4>
                <small class="text-muted">Pending / Partial</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">
                    KES {{ number_format($payments->sum(fn($p) => $p->balance()), 2) }}
                </h4>
                <small class="text-muted">Total Balance</small>
            </div>
        </div>
    </div>

    <div class="stat-card">
        @if($payments->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-receipt"
                   style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">No payment records found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Period</th>
                            <th>Amount Due</th>
                            <th>Amount Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Payment Date</th>
                            <th>Transaction</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $index => $payment)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <span class="fw-semibold">
                                    {{ $payment->monthName() }} {{ $payment->year }}
                                </span>
                            </td>
                            <td>KES {{ number_format($payment->amount_due, 2) }}</td>
                            <td>KES {{ number_format($payment->amount_paid, 2) }}</td>
                            <td>
                                <span class="{{ $payment->balance() > 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                                    KES {{ number_format($payment->balance(), 2) }}
                                </span>
                            </td>
                            <td>{{ $payment->due_date->format('d M Y') }}</td>
                            <td>
                                {{ $payment->payment_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td>
                                @if($payment->transaction_code)
                                    <span class="badge bg-light text-dark border"
                                          style="font-size:0.78rem;">
                                        {{ $payment->transaction_code }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill
                                    @if($payment->status === 'paid') bg-success
                                    @elseif($payment->status === 'partial') bg-warning text-dark
                                    @elseif($payment->status === 'overdue') bg-danger
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td>
                                @if($payment->status !== 'paid')
                                    <a href="{{ route('tenant.pay', $payment) }}"
                                       class="btn btn-sm btn-success"
                                       style="white-space:nowrap;">
                                        <i class="bi bi-phone me-1"></i> Pay Now
                                    </a>
                                @else
                                    <span class="text-success" style="font-size:0.82rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Paid
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
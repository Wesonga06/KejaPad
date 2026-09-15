@extends('layouts.app')

@section('page-title', 'Rent Record Detail')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}" class="nav-link">
        <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}" class="nav-link active">
        <i class="bi bi-cash-coin"></i> Rent
    </a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Rent Record Detail</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                {{ $rent->monthName() }} {{ $rent->year }} —
                {{ $rent->tenant->full_name }}
            </p>
        </div>
        <a href="{{ route('landlord.rent.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="row g-4">
        <!-- Rent Summary -->
        <div class="col-md-5">
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Rent Summary</h6>

                <div class="mb-3">
                    <small class="text-muted d-block">Tenant</small>
                    <span class="fw-semibold">{{ $rent->tenant->full_name }}</span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Property / Unit</small>
                    <span class="fw-semibold">
                        {{ $rent->property->name }} /
                        Unit {{ $rent->unit->unit_number }}
                    </span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Period</small>
                    <span class="fw-semibold">{{ $rent->monthName() }} {{ $rent->year }}</span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Due Date</small>
                    <span class="fw-semibold">{{ $rent->due_date->format('d M Y') }}</span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Amount Due</small>
                    <span class="fw-semibold">KES {{ number_format($rent->amount_due, 2) }}</span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Amount Paid</small>
                    <span class="fw-semibold text-success">
                        KES {{ number_format($rent->amount_paid, 2) }}
                    </span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Balance</small>
                    <span class="fw-semibold {{ $rent->balance() > 0 ? 'text-danger' : 'text-success' }}">
                        KES {{ number_format($rent->balance(), 2) }}
                    </span>
                </div>
                @if($rent->transaction_code)
                <div class="mb-3">
                    <small class="text-muted d-block">Transaction Code</small>
                    <span class="fw-semibold">{{ $rent->transaction_code }}</span>
                </div>
                @endif
                <div class="mb-3">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge rounded-pill
                        @if($rent->status === 'paid') bg-success
                        @elseif($rent->status === 'partial') bg-warning text-dark
                        @elseif($rent->status === 'overdue') bg-danger
                        @else bg-secondary
                        @endif">
                        {{ ucfirst($rent->status) }}
                    </span>
                </div>

                @if($rent->notes)
                <div>
                    <small class="text-muted d-block">Notes</small>
                    <span>{{ $rent->notes }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Record Payment -->
        @if($rent->status !== 'paid')
        <div class="col-md-7">
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-cash-coin me-2 text-success"></i>
                    Record Payment
                </h6>

                <div class="alert alert-info" style="font-size:0.85rem;">
                    <i class="bi bi-info-circle me-2"></i>
                    Outstanding balance:
                    <strong>KES {{ number_format($rent->balance(), 2) }}</strong>
                </div>

                <form method="POST"
                      action="{{ route('landlord.rent.payment', $rent) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount Paid (KES)</label>
                        <input type="number" name="amount_paid"
                               class="form-control @error('amount_paid') is-invalid @enderror"
                               value="{{ old('amount_paid', $rent->balance()) }}"
                               min="1" step="0.01"
                               max="{{ $rent->balance() }}">
                        @error('amount_paid')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date</label>
                        <input type="date" name="payment_date"
                               class="form-control @error('payment_date') is-invalid @enderror"
                               value="{{ old('payment_date', date('Y-m-d')) }}">
                        @error('payment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Transaction Code
                            <span class="text-muted fw-normal">(optional — e.g. M-Pesa code)</span>
                        </label>
                        <input type="text" name="transaction_code"
                               class="form-control @error('transaction_code') is-invalid @enderror"
                               value="{{ old('transaction_code') }}"
                               placeholder="e.g. QHK1234XYZ">
                        @error('transaction_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle me-2"></i> Record Payment
                    </button>
                </form>
            </div>
        </div>
        @else
        <div class="col-md-7">
            <div class="stat-card text-center py-5">
                <i class="bi bi-check-circle"
                   style="font-size:3rem; color:#198754;"></i>
                <h5 class="mt-3 fw-bold text-success">Fully Paid</h5>
                <p class="text-muted">
                    Payment received on
                    {{ $rent->payment_date?->format('d M Y') ?? '—' }}
                </p>
                @if($rent->transaction_code)
                <p class="text-muted">
                    Transaction: <strong>{{ $rent->transaction_code }}</strong>
                </p>
                @endif
            </div>
        </div>
        @endif
    </div>
@endsection
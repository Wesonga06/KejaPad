@extends('layouts.app')

@section('page-title', 'Outstanding Balances')

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
    <a href="{{ route('landlord.rent.index') }}" class="nav-link">
        <i class="bi bi-cash-coin"></i> Rent
    </a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('landlord.reports.index') }}" class="nav-link active">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Outstanding Balances</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                All unpaid and partially paid rent records.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('landlord.reports.export.excel', 'outstanding') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <a href="{{ route('landlord.reports.export.pdf', 'outstanding') }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
            <a href="{{ route('landlord.reports.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="stat-card mb-4 text-center" style="max-width:300px;">
        <p class="stat-label">Total Outstanding</p>
        <h3 class="fw-bold text-danger">
            KES {{ number_format($totalOutstanding, 2) }}
        </h3>
        <small class="text-muted">{{ $records->count() }} records</small>
    </div>

    <div class="stat-card">
        @if($records->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-check-circle"
                   style="font-size:3rem; color:#198754;"></i>
                <p class="mt-3 text-muted fw-semibold">
                    No outstanding balances. All rent is paid!
                </p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0"
                       style="font-size:0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Phone</th>
                            <th>Property / Unit</th>
                            <th>Period</th>
                            <th>Amount Due</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $i => $r)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $r->tenant->full_name }}</td>
                            <td>{{ $r->tenant->phone }}</td>
                            <td>
                                <div>{{ $r->property->name }}</div>
                                <small class="text-muted">
                                    Unit {{ $r->unit->unit_number }}
                                </small>
                            </td>
                            <td>{{ $r->monthName() }} {{ $r->year }}</td>
                            <td>KES {{ number_format($r->amount_due, 2) }}</td>
                            <td>KES {{ number_format($r->amount_paid, 2) }}</td>
                            <td class="text-danger fw-semibold">
                                KES {{ number_format($r->balance(), 2) }}
                            </td>
                            <td>{{ $r->due_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge
                                    @if($r->status === 'overdue') bg-danger
                                    @elseif($r->status === 'partial') bg-warning text-dark
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($r->status) }}
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
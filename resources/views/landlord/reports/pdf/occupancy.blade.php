@extends('layouts.app')

@section('page-title', 'Occupancy Report')

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
            <h5 class="fw-bold mb-1">Occupancy Report</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Unit occupancy status across all properties.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('landlord.reports.export.excel', 'occupancy') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <a href="{{ route('landlord.reports.export.pdf', 'occupancy') }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
            <a href="{{ route('landlord.reports.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-primary">{{ $totalUnits }}</h4>
                <small class="text-muted">Total Units</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger">{{ $occupied }}</h4>
                <small class="text-muted">Occupied</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success">{{ $vacant }}</h4>
                <small class="text-muted">Vacant</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning">{{ $occupancyRate }}%</h4>
                <small class="text-muted">Occupancy Rate</small>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0"
                   style="font-size:0.88rem;">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Property</th>
                        <th>Unit</th>
                        <th>Rent Amount</th>
                        <th>Status</th>
                        <th>Tenant</th>
                        <th>Phone</th>
                        <th>Move-in Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($units as $i => $u)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $u->property->name }}</td>
                        <td class="fw-semibold">{{ $u->unit_number }}</td>
                        <td>KES {{ number_format($u->rent_amount, 2) }}</td>
                        <td>
                            <span class="badge
                                @if($u->status === 'vacant') bg-success
                                @elseif($u->status === 'occupied') bg-danger
                                @else bg-warning text-dark
                                @endif">
                                {{ ucfirst($u->status) }}
                            </span>
                        </td>
                        <td>{{ $u->tenant?->full_name ?? '—' }}</td>
                        <td>{{ $u->tenant?->phone ?? '—' }}</td>
                        <td>
                            {{ $u->tenant?->move_in_date?->format('d M Y') ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
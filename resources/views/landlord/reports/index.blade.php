@extends('layouts.app')

@section('page-title', 'Reports')

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
    <div class="mb-4">
        <h5 class="fw-bold mb-1">Reports</h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Generate and export reports for your properties.
        </p>
    </div>

    <div class="row g-4">
        <!-- Rent Collection -->
        <div class="col-12 col-md-6">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon" style="background:#efe7fb;">
                        <i class="bi bi-cash-stack" style="color:#4c1d95;"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Rent Collection Report</h6>
                        <small class="text-muted">
                            Monthly rent payments and collections
                        </small>
                    </div>
                </div>
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    View rent collected, outstanding balances, and payment
                    status for any month and year.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('landlord.reports.rent-collection') }}"
                       class="btn btn-primary flex-fill">
                        <i class="bi bi-eye me-1"></i> View Report
                    </a>
                    <a href="{{ route('landlord.reports.export.excel', 'rent-collection') }}"
                       class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-excel"></i>
                    </a>
                    <a href="{{ route('landlord.reports.export.pdf', 'rent-collection') }}"
                       class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Outstanding Balances -->
        <div class="col-12 col-md-6">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon" style="background:#fbf3d5;">
                        <i class="bi bi-exclamation-triangle" style="color:#b8860b;"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Outstanding Balances</h6>
                        <small class="text-muted">
                            Unpaid and partially paid rent records
                        </small>
                    </div>
                </div>
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    View all tenants with pending, partial, or overdue rent
                    payments across all your properties.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('landlord.reports.outstanding') }}"
                       class="btn btn-primary flex-fill">
                        <i class="bi bi-eye me-1"></i> View Report
                    </a>
                    <a href="{{ route('landlord.reports.export.excel', 'outstanding') }}"
                       class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-excel"></i>
                    </a>
                    <a href="{{ route('landlord.reports.export.pdf', 'outstanding') }}"
                       class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Occupancy -->
        <div class="col-12 col-md-6">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon" style="background:#e6f9f0;">
                        <i class="bi bi-door-open" style="color:#198754;"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Occupancy Report</h6>
                        <small class="text-muted">
                            Unit occupancy status across properties
                        </small>
                    </div>
                </div>
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    View which units are occupied, vacant, or reserved
                    and who is living in each unit.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('landlord.reports.occupancy') }}"
                       class="btn btn-primary flex-fill">
                        <i class="bi bi-eye me-1"></i> View Report
                    </a>
                    <a href="{{ route('landlord.reports.export.excel', 'occupancy') }}"
                       class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-excel"></i>
                    </a>
                    <a href="{{ route('landlord.reports.export.pdf', 'occupancy') }}"
                       class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Maintenance -->
        <div class="col-12 col-md-6">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon" style="background:#fff3e0;">
                        <i class="bi bi-tools" style="color:#fd7e14;"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Maintenance Report</h6>
                        <small class="text-muted">
                            All maintenance requests and their status
                        </small>
                    </div>
                </div>
                <p class="text-muted mb-3" style="font-size:0.85rem;">
                    View all maintenance requests, assigned caretakers,
                    and resolution status across your properties.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route('landlord.reports.maintenance') }}"
                       class="btn btn-primary flex-fill">
                        <i class="bi bi-eye me-1"></i> View Report
                    </a>
                    <a href="{{ route('landlord.reports.export.excel', 'maintenance') }}"
                       class="btn btn-outline-success">
                        <i class="bi bi-file-earmark-excel"></i>
                    </a>
                    <a href="{{ route('landlord.reports.export.pdf', 'maintenance') }}"
                       class="btn btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
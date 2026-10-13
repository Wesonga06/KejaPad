@extends('layouts.app')

@section('page-title', 'Landlord Dashboard')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link active">
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
    <a href="{{ route('landlord.arrears.index') }}" class="nav-link">
        <i class="bi bi-exclamation-octagon"></i> Arrears
    </a>
    <a href="{{ route('landlord.mpesa-payments') }}" class="nav-link">
    <i class="bi bi-phone"></i> M-Pesa Payments
    </a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('landlord.reports.index') }}" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="p-4 mb-4 rounded-3"
         style="background: linear-gradient(135deg, #4c1d95, #d4af37); color: white;">
        <h4 class="fw-bold mb-1">
            Welcome back, {{ auth()->user()->name }} 👋
        </h4>
        <p class="mb-0 opacity-75">
            Here's your property overview for {{ date('F Y') }}.
        </p>
    </div>

    <!-- Property & Tenant Stats -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total Properties</p>
                        <h3 class="stat-value">{{ $totalProperties }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#efe7fb;">
                        <i class="bi bi-buildings" style="color:#4c1d95;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total Units</p>
                        <h3 class="stat-value">{{ $totalUnits }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#e6f9f0;">
                        <i class="bi bi-door-open" style="color:#198754;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Occupied Units</p>
                        <h3 class="stat-value">{{ $occupiedUnits }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#fff3e0;">
                        <i class="bi bi-house-check" style="color:#fd7e14;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Active Tenants</p>
                        <h3 class="stat-value">{{ $totalTenants }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#fbf3d5;">
                        <i class="bi bi-people" style="color:#b8860b;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rent Stats for Current Month -->
    <h6 class="fw-bold mb-3 text-muted" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">
        Rent — {{ date('F Y') }}
    </h6>
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Rent Expected</p>
                        <h3 class="stat-value" style="font-size:1.4rem;">
                            KES {{ number_format($totalExpected, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#efe7fb;">
                        <i class="bi bi-cash-stack" style="color:#4c1d95;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Rent Collected</p>
                        <h3 class="stat-value" style="font-size:1.4rem;">
                            KES {{ number_format($totalCollected, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#e6f9f0;">
                        <i class="bi bi-check-circle" style="color:#198754;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Outstanding Rent</p>
                        <h3 class="stat-value" style="font-size:1.4rem;">
                            KES {{ number_format($totalOutstanding, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#fff3e0;">
                        <i class="bi bi-hourglass-split" style="color:#fd7e14;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Arrears Overview -->
    <h6 class="fw-bold mb-3 text-muted" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:1px;">
        Arrears Overview
    </h6>
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-4">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total Arrears</p>
                        <h3 class="stat-value" style="font-size:1.4rem; color:#dc3545;">
                            KES {{ number_format($arrearsTotal, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#fbe3e5;">
                        <i class="bi bi-exclamation-octagon" style="color:#dc3545;"></i>
                    </div>
                </div>
                <a href="{{ route('landlord.arrears.index') }}" class="btn btn-sm btn-outline-danger mt-2 w-100">
                    Review Arrears
                </a>
            </div>
        </div>
        <div class="col-12 col-lg-8">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3">Tenants by Rent Status</h6>
                <div class="row g-3 text-center">
                    <div class="col-3">
                        <div class="fw-bold" style="font-size:1.4rem; color:#198754;">{{ $statusCounts['green'] }}</div>
                        <small class="text-muted">Up to date</small>
                    </div>
                    <div class="col-3">
                        <div class="fw-bold" style="font-size:1.4rem; color:#dc3545;">{{ $statusCounts['red'] }}</div>
                        <small class="text-muted">Overdue</small>
                    </div>
                    <div class="col-3">
                        <div class="fw-bold" style="font-size:1.4rem; color:#fd7e14;">{{ $statusCounts['orange'] }}</div>
                        <small class="text-muted">Escalated</small>
                    </div>
                    <div class="col-3">
                        <div class="fw-bold" style="font-size:1.4rem; color:#b8860b;">{{ $statusCounts['yellow'] }}</div>
                        <small class="text-muted">Further action</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="stat-card">
        <h6 class="fw-bold mb-3">Quick Actions</h6>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('landlord.properties.create') }}"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Property
            </a>
            <a href="{{ route('landlord.tenants.create') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-person-plus me-1"></i> Add Tenant
            </a>
            <a href="{{ route('landlord.rent.index') }}"
               class="btn btn-outline-warning btn-sm">
                <i class="bi bi-cash-coin me-1"></i> Manage Rent
            </a>
            <a href="{{ route('landlord.tenants.index') }}"
               class="btn btn-outline-info btn-sm">
                <i class="bi bi-people me-1"></i> View Tenants
            </a>
        </div>
    </div>
@endsection
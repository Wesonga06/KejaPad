@extends('layouts.app')

@section('page-title', 'Tenant Details')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}" class="nav-link active">
        <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}" class="nav-link">
    <i class="bi bi-cash-coin"></i> Rent
</a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Tenant Details</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Full profile for {{ $tenant->full_name }}.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('landlord.tenants.edit', $tenant) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i> Edit
            </a>
            <a href="{{ route('landlord.tenants.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="mx-auto mb-3" style="width:70px; height:70px; background:linear-gradient(135deg,#4cc9f0,#0f3460); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:1.5rem;">
                    {{ strtoupper(substr($tenant->full_name, 0, 2)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $tenant->full_name }}</h5>
                <p class="text-muted mb-2" style="font-size:0.85rem;">{{ $tenant->phone }}</p>
                <span class="badge {{ $tenant->is_active ? 'bg-success' : 'bg-secondary' }} mb-3">
                    {{ $tenant->is_active ? 'Active Tenant' : 'Inactive' }}
                </span>

                <div class="text-start border-top pt-3 mt-2">
                    <div class="mb-2">
                        <small class="text-muted d-block">Property</small>
                        <span class="fw-semibold">{{ $tenant->property->name }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Unit</small>
                        <span class="fw-semibold">{{ $tenant->unit->unit_number }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Rent Amount</small>
                        <span class="fw-semibold">KES {{ number_format($tenant->unit->rent_amount, 2) }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Move-in Date</small>
                        <span class="fw-semibold">{{ $tenant->move_in_date->format('d M Y') }}</span>
                    </div>
                    @if($tenant->move_out_date)
                    <div class="mb-2">
                        <small class="text-muted d-block">Move-out Date</small>
                        <span class="fw-semibold">{{ $tenant->move_out_date->format('d M Y') }}</span>
                    </div>
                    @endif
                    <div>
                        <small class="text-muted d-block">Has Login Account</small>
                        <span class="fw-semibold">{{ $tenant->user_id ? 'Yes' : 'No' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Card -->
        <div class="col-md-8">
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Contact Information</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Email</small>
                        <span>{{ $tenant->email ?? '—' }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Phone</small>
                        <span>{{ $tenant->phone }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">National ID</small>
                        <span>{{ $tenant->national_id ?? '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Emergency Contact</h6>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Name</small>
                        <span>{{ $tenant->emergency_contact_name ?? '—' }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Phone</small>
                        <span>{{ $tenant->emergency_contact_phone ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
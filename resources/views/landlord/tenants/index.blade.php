@extends('layouts.app')

@section('page-title', 'Tenants')

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
            <h5 class="fw-bold mb-1">All Tenants</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Manage tenants across all your properties.</p>
        </div>
        <a href="{{ route('landlord.tenants.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> Add Tenant
        </a>
    </div>

    @if($tenants->isEmpty())
        <div class="stat-card text-center py-5">
            <i class="bi bi-people" style="font-size:3rem; color:#dee2e6;"></i>
            <p class="mt-3 text-muted">No tenants yet. Add your first tenant.</p>
            <a href="{{ route('landlord.tenants.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle me-2"></i> Add Tenant
            </a>
        </div>
    @else
        <div class="stat-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Phone</th>
                            <th>Property</th>
                            <th>Unit</th>
                            <th>Move-in Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenants as $index => $tenant)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:34px; height:34px; background:linear-gradient(135deg,#4cc9f0,#0f3460); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:0.8rem; flex-shrink:0;">
                                        {{ strtoupper(substr($tenant->full_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:0.9rem;">{{ $tenant->full_name }}</div>
                                        <div class="text-muted" style="font-size:0.8rem;">{{ $tenant->email ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $tenant->phone }}</td>
                            <td>{{ $tenant->property->name }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $tenant->unit->unit_number }}</span></td>
                            <td>{{ $tenant->move_in_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $tenant->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('landlord.tenants.show', $tenant) }}"
                                       class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('landlord.tenants.edit', $tenant) }}"
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($tenant->is_active)
                                    <form method="POST"
                                          action="{{ route('landlord.tenants.deactivate', $tenant) }}"
                                          onsubmit="return confirm('Deactivate this tenant and free the unit?')">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Deactivate">
                                            <i class="bi bi-person-x"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
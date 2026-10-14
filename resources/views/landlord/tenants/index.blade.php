@extends('layouts.app')

@section('page-title', 'Tenants')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Tenants</h1>
            <p class="kp-page-subtitle">Manage tenants across all your properties.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.tenants.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i> Add Tenant
            </a>
        </div>
    </div>

    <div class="kp-card mb-4">
        <form method="GET" action="{{ route('landlord.tenants.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label" for="q">Search</label>
                <input type="search" name="q" id="q" class="form-control"
                       value="{{ request('q') }}" placeholder="Name, phone or email">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="property_id">Property</label>
                <select name="property_id" id="property_id" class="form-select">
                    <option value="">All properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected(request('property_id') == $property->id)>
                            {{ $property->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label" for="status">Status</label>
                <select name="status" id="status" class="form-select">
                    <option value="">Any</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <a href="{{ route('landlord.tenants.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="kp-card">
        @if($tenants->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-people"></i>
                <p class="mb-3">No tenants found. Add your first tenant to get started.</p>
                <a href="{{ route('landlord.tenants.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-2"></i> Add Tenant
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Phone</th>
                            <th>Property</th>
                            <th>Unit</th>
                            <th>Move-in Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenants as $index => $tenant)
                        <tr>
                            <td class="text-muted">{{ $tenants->firstItem() + $index }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="kp-avatar sm">
                                        {{ strtoupper(substr($tenant->full_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:0.9rem;">{{ $tenant->full_name }}</div>
                                        <div class="text-muted" style="font-size:0.78rem;">{{ $tenant->email ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $tenant->phone }}</td>
                            <td>{{ $tenant->property->name }}</td>
                            <td><span class="badge kp-badge-purple">{{ $tenant->unit->unit_number }}</span></td>
                            <td>{{ $tenant->move_in_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge {{ $tenant->is_active ? 'kp-badge-success' : 'kp-badge-muted' }}">
                                    {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('landlord.tenants.show', $tenant) }}"
                                       class="btn btn-sm btn-outline-info" title="View account">
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

            @if($tenants->hasPages())
                <div class="mt-3">{{ $tenants->links() }}</div>
            @endif
        @endif
    </div>
@endsection

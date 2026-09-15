@extends('layouts.app')

@section('page-title', 'Maintenance Requests')

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
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link active">
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
    <div class="mb-4">
        <h5 class="fw-bold mb-1">Maintenance Requests</h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            View and manage all maintenance requests across your properties.
        </p>
    </div>

    <!-- Filters -->
    <div class="stat-card mb-4">
        <form method="GET" action="{{ route('landlord.maintenance.index') }}"
              class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending"     {{ request('status') === 'pending'     ? 'selected' : '' }}>Pending</option>
                    <option value="assigned"    {{ request('status') === 'assigned'    ? 'selected' : '' }}>Assigned</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved"    {{ request('status') === 'resolved'    ? 'selected' : '' }}>Resolved</option>
                    <option value="closed"      {{ request('status') === 'closed'      ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <option value="plumbing"   {{ request('category') === 'plumbing'   ? 'selected' : '' }}>Plumbing</option>
                    <option value="electrical" {{ request('category') === 'electrical' ? 'selected' : '' }}>Electrical</option>
                    <option value="security"   {{ request('category') === 'security'   ? 'selected' : '' }}>Security</option>
                    <option value="cleaning"   {{ request('category') === 'cleaning'   ? 'selected' : '' }}>Cleaning</option>
                    <option value="general"    {{ request('category') === 'general'    ? 'selected' : '' }}>General</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Priority</label>
                <select name="priority" class="form-select">
                    <option value="">All Priorities</option>
                    <option value="low"    {{ request('priority') === 'low'    ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high"   {{ request('priority') === 'high'   ? 'selected' : '' }}>High</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('landlord.maintenance.index') }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="stat-card">
        @if($requests->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-tools" style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">No maintenance requests found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Tenant / Unit</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $index => $req)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $req->title }}</td>
                            <td>
                                <div style="font-size:0.88rem;">
                                    {{ $req->tenant->full_name }}
                                </div>
                                <div class="text-muted" style="font-size:0.8rem;">
                                    Unit {{ $req->unit->unit_number }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $req->categoryLabel() }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $req->priorityColor() }}">
                                    {{ ucfirst($req->priority) }}
                                </span>
                            </td>
                            <td>
                                {{ $req->assignedTo?->name ?? '—' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $req->statusColor() }}">
                                    {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                                </span>
                            </td>
                            <td>{{ $req->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('landlord.maintenance.show', $req) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
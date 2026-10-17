@extends('layouts.app')

@section('page-title', 'Maintenance Requests')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1">Maintenance Requests</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Requests across your properties, with assignment and verification.
            </p>
        </div>
        <a href="{{ route('landlord.caretakers.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-person-check me-1"></i> Caretaker Overview
        </a>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-secondary mb-0">{{ $counts['pending'] }}</h4>
                <small class="text-muted">Pending</small>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning mb-0">{{ $counts['in_progress'] }}</h4>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">{{ $counts['overdue'] }}</h4>
                <small class="text-muted">Overdue</small>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card text-center">
                <h4 class="fw-bold mb-0" style="color:#b7791f;">{{ $counts['awaiting_verification'] }}</h4>
                <small class="text-muted">Awaiting Verification</small>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-primary mb-0">{{ $counts['unassigned'] }}</h4>
                <small class="text-muted">Unassigned</small>
            </div>
        </div>
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
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Caretaker</label>
                <select name="assigned_to" class="form-select">
                    <option value="">All Caretakers</option>
                    @foreach($caretakers as $caretaker)
                        <option value="{{ $caretaker->id }}" {{ (string) request('assigned_to') === (string) $caretaker->id ? 'selected' : '' }}>
                            {{ $caretaker->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Quick Filter</label>
                <select name="scope" class="form-select">
                    <option value="">None</option>
                    <option value="overdue"              {{ request('scope') === 'overdue'              ? 'selected' : '' }}>Overdue</option>
                    <option value="awaiting_verification"{{ request('scope') === 'awaiting_verification'? 'selected' : '' }}>Awaiting verification</option>
                    <option value="unassigned"           {{ request('scope') === 'unassigned'           ? 'selected' : '' }}>Unassigned</option>
                    <option value="returned"             {{ request('scope') === 'returned'             ? 'selected' : '' }}>Returned for rework</option>
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
                            <th>Caretaker</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Flags</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $request_item)
                            <tr>
                                <td>{{ $requests->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $request_item->title }}</div>
                                    <small class="text-muted">{{ $request_item->categoryLabel() }} &bull; {{ ucfirst($request_item->priority) }}</small>
                                </td>
                                <td>
                                    <div>{{ $request_item->tenant?->full_name ?? '—' }}</div>
                                    <small class="text-muted">{{ $request_item->property?->name }} &bull; {{ $request_item->unit?->unit_number }}</small>
                                </td>
                                <td>{{ $request_item->assignedTo?->name ?? '—' }}</td>
                                <td>
                                    @if($request_item->due_date)
                                        <span class="{{ $request_item->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                                            {{ $request_item->due_date->format('d M Y') }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $request_item->statusColor() }}">
                                        {{ ucfirst(str_replace('_', ' ', $request_item->status)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($request_item->isOverdue())
                                        <span class="kp-badge kp-badge-danger">Overdue</span>
                                    @endif
                                    @if($request_item->isAwaitingVerification())
                                        <span class="kp-badge kp-badge-warning">Verify</span>
                                    @endif
                                    @if($request_item->isReturnedForRework())
                                        <span class="kp-badge kp-badge-purple">Rework</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('landlord.maintenance.show', $request_item) }}"
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

    @if($requests->hasPages())
        <div class="mt-3">{{ $requests->links() }}</div>
    @endif
@endsection

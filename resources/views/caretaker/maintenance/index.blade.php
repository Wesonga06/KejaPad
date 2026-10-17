@extends('layouts.app')

@section('page-title', 'Maintenance Requests')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1">Maintenance Requests</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Requests for the properties you manage.
            </p>
        </div>
        <a href="{{ route('caretaker.history.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-clipboard-data me-1"></i> My Work History
        </a>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-secondary mb-0">{{ $counts['pending'] }}</h4>
                <small class="text-muted">Pending</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning mb-0">{{ $counts['in_progress'] }}</h4>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">{{ $counts['overdue'] }}</h4>
                <small class="text-muted">Overdue</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold mb-0" style="color:#b7791f;">{{ $counts['awaiting_verification'] }}</h4>
                <small class="text-muted">Awaiting Verification</small>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="stat-card mb-4">
        <form method="GET" action="{{ route('caretaker.maintenance.index') }}" class="row g-3 align-items-end">
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
                <label class="form-label fw-semibold">Property</label>
                <select name="property_id" class="form-select">
                    <option value="">All Properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" {{ (string) request('property_id') === (string) $property->id ? 'selected' : '' }}>
                            {{ $property->name }}
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
                    <option value="returned"             {{ request('scope') === 'returned'             ? 'selected' : '' }}>Returned for rework</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2 align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="mine" value="1" id="mine" {{ request()->boolean('mine') ? 'checked' : '' }}>
                    <label class="form-check-label" for="mine">Only mine</label>
                </div>
                <button type="submit" class="btn btn-outline-primary flex-fill"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('caretaker.maintenance.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>

    <div class="stat-card">
        @if($requests->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-clipboard-check" style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">No maintenance requests for your properties yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
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
                                <td>{{ $request_item->tenant?->full_name ?? '—' }}</td>
                                <td>
                                    <div>{{ $request_item->property?->name }}</div>
                                    <small class="text-muted">{{ $request_item->unit?->unit_number }}</small>
                                </td>
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
                                    @if($request_item->isReturnedForRework())
                                        <span class="kp-badge kp-badge-purple">Rework</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('caretaker.maintenance.show', $request_item) }}" class="btn btn-sm btn-outline-primary">
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

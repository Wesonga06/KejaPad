@extends('layouts.app')

@section('page-title', 'My Tasks')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('caretaker.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Tasks</div>
    <a href="{{ route('caretaker.maintenance.index') }}" class="nav-link active">
        <i class="bi bi-tools"></i> Maintenance Requests
    </a>
@endsection

@section('content')
    <div class="mb-4">
        <h5 class="fw-bold mb-1">My Assigned Tasks</h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Maintenance requests assigned to you.
        </p>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-secondary mb-0">{{ $pendingCount }}</h4>
                <small class="text-muted">Pending</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning mb-0">{{ $inProgressCount }}</h4>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success mb-0">{{ $resolvedCount }}</h4>
                <small class="text-muted">Resolved</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-primary mb-0">{{ $requests->count() }}</h4>
                <small class="text-muted">Total</small>
            </div>
        </div>
    </div>

    <div class="stat-card">
        @if($requests->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-clipboard-check"
                   style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">No tasks assigned to you yet.</p>
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
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $index => $req)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-semibold">{{ $req->title }}</td>
                            <td>{{ $req->tenant->full_name }}</td>
                            <td>
                                <div style="font-size:0.85rem;">{{ $req->property->name }}</div>
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
                                <span class="badge bg-{{ $req->statusColor() }}">
                                    {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('caretaker.maintenance.show', $req) }}"
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
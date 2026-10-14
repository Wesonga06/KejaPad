@extends('layouts.app')

@section('page-title', 'Maintenance Report')


@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Maintenance Report</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                All maintenance requests across your properties.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('landlord.reports.export.excel', 'maintenance') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <a href="{{ route('landlord.reports.export.pdf', 'maintenance') }}"
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
                <h4 class="fw-bold text-secondary">{{ $pending }}</h4>
                <small class="text-muted">Pending</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning">{{ $inProgress }}</h4>
                <small class="text-muted">In Progress</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success">{{ $resolved }}</h4>
                <small class="text-muted">Resolved</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-dark">{{ $closed }}</h4>
                <small class="text-muted">Closed</small>
            </div>
        </div>
    </div>

    <div class="stat-card">
        @if($requests->isEmpty())
            <div class="text-center py-4">
                <i class="bi bi-tools" style="font-size:2rem; color:#dee2e6;"></i>
                <p class="mt-2 text-muted">No maintenance requests found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0"
                       style="font-size:0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Resolved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $i => $req)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $req->title }}</td>
                            <td>{{ $req->tenant->full_name }}</td>
                            <td>
                                <div>{{ $req->property->name }}</div>
                                <small class="text-muted">
                                    Unit {{ $req->unit->unit_number }}
                                </small>
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
                            <td>{{ $req->assignedTo?->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $req->statusColor() }}">
                                    {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                                </span>
                            </td>
                            <td>{{ $req->created_at->format('d M Y') }}</td>
                            <td>
                                {{ $req->resolved_at?->format('d M Y') ?? '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
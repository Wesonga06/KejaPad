@extends('layouts.app')

@section('page-title', 'Maintenance Requests')


@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">My Maintenance Requests</h1>
            <p class="kp-page-subtitle">
                Track the status of your submitted requests.
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('tenant.maintenance.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> New Request
            </a>
        </div>
    </div>

    @if($requests->isEmpty())
        <div class="stat-card text-center py-5">
            <i class="bi bi-tools" style="font-size:3rem; color:#dee2e6;"></i>
            <p class="mt-3 text-muted">No maintenance requests yet.</p>
            <a href="{{ route('tenant.maintenance.create') }}"
               class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle me-2"></i> Submit Request
            </a>
        </div>
    @else
        <div class="stat-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Due</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $index => $req)
                        <tr>
                            <td>{{ $requests->firstItem() + $index }}</td>
                            <td class="fw-semibold">{{ $req->title }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-tag me-1"></i>
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
                                @if($req->isVerified())
                                    <span class="kp-badge kp-badge-success">Verified</span>
                                @elseif($req->isAwaitingVerification())
                                    <span class="kp-badge kp-badge-warning">Awaiting confirmation</span>
                                @elseif($req->isOverdue())
                                    <span class="kp-badge kp-badge-danger">Overdue</span>
                                @endif
                            </td>
                            <td>
                                @if($req->due_date)
                                    <span class="{{ $req->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                                        {{ $req->due_date->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $req->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('tenant.maintenance.show', $req) }}"
                                   class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($requests->hasPages())
        <div class="mt-3">{{ $requests->links() }}</div>
    @endif
@endsection

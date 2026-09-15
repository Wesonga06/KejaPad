@extends('layouts.app')

@section('page-title', 'Maintenance Requests')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('tenant.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('tenant.announcements.index') }}" class="nav-link active">
        <i class="bi bi-megaphone"></i> Announcements
    </a>

    <div class="nav-section-title">My Account</div>
    <a href="{{ route('tenant.payments') }}" class="nav-link">
        <i class="bi bi-receipt"></i> Payment History
    </a>
    <a href="{{ route('tenant.maintenance.index') }}" class="nav-link active">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('tenant.profile') }}" class="nav-link">
        <i class="bi bi-person-circle"></i> My Profile
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">My Maintenance Requests</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Track the status of your submitted requests.
            </p>
        </div>
        <a href="{{ route('tenant.maintenance.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> New Request
        </a>
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
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $index => $req)
                        <tr>
                            <td>{{ $index + 1 }}</td>
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
@endsection
@extends('layouts.app')

@section('page-title', 'Maintenance Request Detail')

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
    <a href="{{ route('landlord.reports.index') }}" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">{{ $maintenance->title }}</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Submitted by {{ $maintenance->tenant->full_name }}
                on {{ $maintenance->created_at->format('d M Y') }}
            </p>
        </div>
        <a href="{{ route('landlord.maintenance.index') }}"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <!-- Request Info -->
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Request Details</h6>
                <div class="mb-3">
                    <small class="text-muted d-block">Description</small>
                    <p class="mb-0">{{ $maintenance->description }}</p>
                </div>
                <div class="row g-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Category</small>
                        <span class="badge bg-light text-dark border">
                            {{ $maintenance->categoryLabel() }}
                        </span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Priority</small>
                        <span class="badge bg-{{ $maintenance->priorityColor() }}">
                            {{ ucfirst($maintenance->priority) }}
                        </span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-{{ $maintenance->statusColor() }}">
                            {{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}
                        </span>
                    </div>
                </div>

                @if($maintenance->resolution_notes)
                <div class="mt-3">
                    <small class="text-muted d-block">Resolution Notes</small>
                    <p class="mb-0">{{ $maintenance->resolution_notes }}</p>
                </div>
                @endif
            </div>

            <!-- Assign to Caretaker -->
            @if($maintenance->status !== 'resolved' && $maintenance->status !== 'closed')
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-person-gear me-2"></i>Assign to Caretaker
                </h6>
                <form method="POST"
                      action="{{ route('landlord.maintenance.assign', $maintenance) }}"
                      class="d-flex gap-3">
                    @csrf
                    <select name="assigned_to" class="form-select">
                        <option value="">-- Select Caretaker --</option>
                        @foreach($caretakers as $caretaker)
                            <option value="{{ $caretaker->id }}"
                                {{ $maintenance->assigned_to == $caretaker->id ? 'selected' : '' }}>
                                {{ $caretaker->name }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" style="white-space:nowrap;">
                        <i class="bi bi-person-check me-1"></i> Assign
                    </button>
                </form>
            </div>

            <!-- Update Status -->
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Update Status</h6>
                <form method="POST"
                      action="{{ route('landlord.maintenance.status', $maintenance) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending"     {{ $maintenance->status === 'pending'     ? 'selected' : '' }}>Pending</option>
                            <option value="assigned"    {{ $maintenance->status === 'assigned'    ? 'selected' : '' }}>Assigned</option>
                            <option value="in_progress" {{ $maintenance->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved"    {{ $maintenance->status === 'resolved'    ? 'selected' : '' }}>Resolved</option>
                            <option value="closed"      {{ $maintenance->status === 'closed'      ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Resolution Notes
                            <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <textarea name="resolution_notes" rows="2"
                                  class="form-control"
                                  placeholder="Describe how the issue was resolved">{{ $maintenance->resolution_notes }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i> Update Status
                    </button>
                </form>
            </div>
            @endif
        </div>

        <div class="col-md-5">
            <!-- Tenant Info -->
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Tenant Info</h6>
                <div class="mb-2">
                    <small class="text-muted d-block">Name</small>
                    <span class="fw-semibold">{{ $maintenance->tenant->full_name }}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Phone</small>
                    <span>{{ $maintenance->tenant->phone }}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Property</small>
                    <span>{{ $maintenance->property->name }}</span>
                </div>
                <div>
                    <small class="text-muted d-block">Unit</small>
                    <span>{{ $maintenance->unit->unit_number }}</span>
                </div>
            </div>

            <!-- Photo -->
            @if($maintenance->photo)
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Attached Photo</h6>
                <img src="{{ asset('storage/' . $maintenance->photo) }}"
                     alt="Issue photo"
                     class="w-100 rounded-2"
                     style="max-height:250px; object-fit:cover;">
            </div>
            @endif
        </div>
    </div>
@endsection
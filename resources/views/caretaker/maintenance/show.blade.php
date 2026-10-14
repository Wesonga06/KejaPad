@extends('layouts.app')

@section('page-title', 'Task Detail')


@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">{{ $maintenance->title }}</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                {{ $maintenance->property->name }} —
                Unit {{ $maintenance->unit->unit_number }}
            </p>
        </div>
        <a href="{{ route('caretaker.maintenance.index') }}"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
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
            </div>

            <!-- Update Status -->
            @if(!in_array($maintenance->status, ['resolved', 'closed']))
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Update Progress</h6>
                <form method="POST"
                      action="{{ route('caretaker.maintenance.status', $maintenance) }}">
                    @csrf @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="in_progress"
                                {{ $maintenance->status === 'in_progress' ? 'selected' : '' }}>
                                In Progress
                            </option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Resolution Notes
                            <span class="text-muted fw-normal">(required if resolved)</span>
                        </label>
                        <textarea name="resolution_notes" rows="3"
                                  class="form-control"
                                  placeholder="Describe what was done to fix the issue">{{ $maintenance->resolution_notes }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle me-2"></i> Update Status
                    </button>
                </form>
            </div>
            @else
            <div class="stat-card text-center py-4">
                <i class="bi bi-check-circle"
                   style="font-size:2.5rem; color:#168653;"></i>
                <h6 class="mt-2 fw-bold text-success">Task Completed</h6>
                @if($maintenance->resolution_notes)
                    <p class="text-muted mb-0" style="font-size:0.85rem;">
                        {{ $maintenance->resolution_notes }}
                    </p>
                @endif
            </div>
            @endif
        </div>

        <div class="col-md-5">
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
                <div>
                    <small class="text-muted d-block">Unit</small>
                    <span>{{ $maintenance->unit->unit_number }}</span>
                </div>
            </div>

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
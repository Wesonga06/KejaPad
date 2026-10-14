@extends('layouts.app')

@section('page-title', 'Request Details')


@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">{{ $maintenance->title }}</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Submitted {{ $maintenance->created_at->format('d M Y') }}
            </p>
        </div>
        <a href="{{ route('tenant.maintenance.index') }}"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Request Details</h6>

                <div class="mb-3">
                    <small class="text-muted d-block">Title</small>
                    <span class="fw-semibold">{{ $maintenance->title }}</span>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">Description</small>
                    <p class="mb-0">{{ $maintenance->description }}</p>
                </div>
                <div class="row g-3 mb-3">
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

                @if($maintenance->assignedTo)
                <div class="mb-3">
                    <small class="text-muted d-block">Assigned To</small>
                    <span class="fw-semibold">{{ $maintenance->assignedTo->name }}</span>
                </div>
                @endif

                @if($maintenance->resolution_notes)
                <div class="mb-3">
                    <small class="text-muted d-block">Resolution Notes</small>
                    <p class="mb-0">{{ $maintenance->resolution_notes }}</p>
                </div>
                @endif

                @if($maintenance->resolved_at)
                <div>
                    <small class="text-muted d-block">Resolved At</small>
                    <span>{{ $maintenance->resolved_at->format('d M Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-5">
            @if($maintenance->photo)
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Attached Photo</h6>
                <img src="{{ asset('storage/' . $maintenance->photo) }}"
                     alt="Issue photo"
                     class="w-100 rounded-2"
                     style="max-height:250px; object-fit:cover;">
            </div>
            @endif

            <!-- Status Timeline -->
            <div class="stat-card mt-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Status Timeline</h6>
                @php
                    $statuses = ['pending', 'assigned', 'in_progress', 'resolved', 'closed'];
                    $current  = $maintenance->status;
                    $reached  = false;
                @endphp
                @foreach($statuses as $s)
                    @php
                        $isActive  = $s === $current;
                        $isPast    = !$reached && !$isActive;
                        if ($isActive) $reached = true;
                    @endphp
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div style="width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.75rem; flex-shrink:0;
                            background: {{ $isActive ? '#5926C8' : ($isPast ? '#168653' : '#dee2e6') }};
                            color: {{ ($isActive || $isPast) ? 'white' : '#6c757d' }};">
                            @if($isPast)
                                <i class="bi bi-check"></i>
                            @elseif($isActive)
                                <i class="bi bi-circle-fill" style="font-size:0.5rem;"></i>
                            @else
                                <i class="bi bi-circle" style="font-size:0.5rem;"></i>
                            @endif
                        </div>
                        <span style="font-size:0.85rem; {{ $isActive ? 'font-weight:600;' : '' }} color: {{ $isPast ? '#168653' : ($isActive ? '#5926C8' : '#adb5bd') }};">
                            {{ ucfirst(str_replace('_', ' ', $s)) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
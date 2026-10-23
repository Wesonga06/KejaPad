@extends('layouts.app')

@section('page-title', 'Request Details')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">{{ $maintenance->title }}</h1>
            <p class="kp-page-subtitle">
                Submitted {{ $maintenance->created_at->format('d M Y') }}
                &bull;
                <span class="badge bg-{{ $maintenance->statusColor() }}">{{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}</span>
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('tenant.maintenance.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    @if($maintenance->isVerified())
        <div class="alert alert-success">
            <i class="bi bi-patch-check me-1"></i>
            <strong>Verified complete</strong>
            by your landlord on {{ optional($maintenance->verified_at)->format('d M Y') }}.
            @if($maintenance->verification_notes)<br>{{ $maintenance->verification_notes }}@endif
        </div>
    @elseif($maintenance->isAwaitingVerification())
        <div class="alert alert-warning">
            <i class="bi bi-hourglass-split me-1"></i>
            <strong>Awaiting verification</strong> — the work has been submitted and is being confirmed.
        </div>
    @elseif($maintenance->isReturnedForRework())
        <div class="alert alert-info">
            <i class="bi bi-arrow-counterclockwise me-1"></i>
            <strong>Further work in progress</strong> — the landlord has asked for more to be done.
        </div>
    @endif

    <div class="row g-4">
        <div class="col-md-7">
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Request Details</h6>

                <div class="mb-3">
                    <small class="text-muted d-block">Description</small>
                    <p class="mb-0">{{ $maintenance->description }}</p>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Category</small>
                        <span class="badge bg-light text-dark border">{{ $maintenance->categoryLabel() }}</span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Priority</small>
                        <span class="badge bg-{{ $maintenance->priorityColor() }}">{{ ucfirst($maintenance->priority) }}</span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Deadline</small>
                        <span class="{{ $maintenance->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                            {{ $maintenance->due_date?->format('d M Y') ?? 'Not set' }}
                        </span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Assigned to</small>
                        <span class="fw-semibold">{{ $maintenance->assignedTo?->name ?? 'Not yet assigned' }}</span>
                    </div>
                    @if($maintenance->resolution_notes)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Latest Update</small>
                            <span>{{ $maintenance->resolution_notes }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Progress timeline -->
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-activity me-2"></i>Progress</h6>
                @forelse($timeline as $event)
                    <div class="d-flex gap-3 py-2" style="border-bottom:1px solid var(--kp-border);">
                        <span class="kp-badge {{ $event->badgeClass() }}" style="white-space:nowrap;">{{ $event->label() }}</span>
                        <div class="flex-grow-1">
                            @if($event->message)
                                <div style="font-size:0.88rem;">{{ $event->message }}</div>
                            @endif
                            <small class="text-muted">{{ $event->created_at->format('d M Y H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0" style="font-size:0.85rem;">
                        Your request has been received and will be reviewed shortly.
                    </p>
                @endforelse
            </div>

            <!-- Evidence -->
            @if($maintenance->evidence->isNotEmpty())
                <div class="stat-card">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-paperclip me-2"></i>Attachments</h6>
                    @foreach($maintenance->evidence as $file)
                        <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--kp-border);">
                            <i class="bi {{ $file->isImage() ? 'bi-file-earmark-image' : 'bi-file-earmark-pdf' }}" style="font-size:1.2rem; color:var(--kp-royal);"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size:0.88rem;">{{ $file->original_name }}</div>
                                <small class="text-muted">{{ $file->typeLabel() }} &bull; {{ $file->humanSize() }}</small>
                            </div>
                            <a href="{{ route('maintenance.evidence.show', $file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="col-md-5">
            @if($maintenance->photo)
                <div class="stat-card mb-4">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom">Attached Photo</h6>
                    <img src="{{ asset('storage/' . $maintenance->photo) }}"
                         alt="Issue photo"
                         class="w-100 rounded-2"
                         style="max-height:250px; object-fit:cover;">
                </div>
            @endif

            <!-- Status Timeline -->
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Where things stand</h6>
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
                    <div class="d-flex align-items-center gap-3 py-2">
                        <div class="kp-kpi-icon {{ $isPast || $isActive ? 'green' : '' }}"
                             style="width:28px;height:28px;font-size:0.8rem; {{ !$isPast && !$isActive ? 'background:var(--kp-border); color:var(--kp-muted);' : '' }}">
                            @if($isPast || $isActive)
                                <i class="bi {{ $isActive ? 'bi-record-circle' : 'bi-check' }}"></i>
                            @else
                                <i class="bi bi-circle"></i>
                            @endif
                        </div>
                        <span class="{{ $isActive ? 'fw-semibold' : '' }}" style="font-size:0.88rem;">
                            {{ ucfirst(str_replace('_', ' ', $s)) }}
                        </span>
                    </div>
                @endforeach
                @if($maintenance->isOverdue())
                    <p class="text-danger mb-0 mt-2" style="font-size:0.8rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i> This request is past its deadline.
                    </p>
                @endif
            </div>
        </div>
    </div>
@endsection

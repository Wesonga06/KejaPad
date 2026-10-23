@extends('layouts.app')

@section('page-title', 'Task Detail')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">{{ $maintenance->title }}</h1>
            <p class="kp-page-subtitle">
                {{ $maintenance->property?->name }} — Unit {{ $maintenance->unit?->unit_number }}
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('caretaker.maintenance.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    @if($maintenance->isReturnedForRework())
        <div class="alert alert-warning">
            <i class="bi bi-arrow-counterclockwise me-1"></i>
            <strong>Returned for further work</strong>
            by {{ $maintenance->returnedBy?->name ?? '—' }}
            on {{ optional($maintenance->returned_at)->format('d M Y') }}.
            @if($maintenance->return_reason)<br>{{ $maintenance->return_reason }}@endif
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
                <div class="row g-3">
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Category</small>
                        <span class="badge bg-light text-dark border">{{ $maintenance->categoryLabel() }}</span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Priority</small>
                        <span class="badge bg-{{ $maintenance->priorityColor() }}">{{ ucfirst($maintenance->priority) }}</span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge bg-{{ $maintenance->statusColor() }}">{{ ucfirst(str_replace('_', ' ', $maintenance->status)) }}</span>
                    </div>
                    <div class="col-sm-3">
                        <small class="text-muted d-block">Deadline</small>
                        <span class="{{ $maintenance->isOverdue() ? 'text-danger fw-semibold' : '' }}">
                            {{ $maintenance->due_date?->format('d M Y') ?? 'Not set' }}
                        </span>
                    </div>
                </div>
            </div>

            @if($maintenance->isOpen())
                @if($maintenance->status !== 'in_progress')
                    <div class="stat-card mb-4">
                        <h6 class="fw-bold mb-3 pb-2 border-bottom">Start Work</h6>
                        <form method="POST" action="{{ route('caretaker.maintenance.status', $maintenance) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-play-circle me-2"></i> Mark as In Progress
                            </button>
                        </form>
                    </div>
                @endif

                <div class="stat-card mb-4">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-send-check me-2"></i>Submit for Verification</h6>
                    <p class="text-muted" style="font-size:0.85rem;">
                        Completing a task submits it to the landlord for verification. It is only closed once the landlord confirms.
                    </p>
                    <form method="POST" action="{{ route('caretaker.maintenance.complete', $maintenance) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">What did you do?</label>
                            <textarea name="resolution_notes" rows="3" class="form-control" placeholder="Describe the work completed" required>{{ $maintenance->resolution_notes }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Evidence <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="file" name="evidence[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-send-check me-2"></i> Submit for Verification
                        </button>
                    </form>
                </div>
            @elseif($maintenance->isAwaitingVerification())
                <div class="stat-card text-center py-4">
                    <i class="bi bi-hourglass-split" style="font-size:2.5rem; color:#b7791f;"></i>
                    <h6 class="mt-2 fw-bold" style="color:#b7791f;">Awaiting Landlord Verification</h6>
                    <p class="text-muted mb-0" style="font-size:0.85rem;">
                        You submitted this task as complete. The landlord will confirm or return it.
                    </p>
                </div>
            @elseif($maintenance->isVerified())
                <div class="stat-card text-center py-4">
                    <i class="bi bi-patch-check" style="font-size:2.5rem; color:#168653;"></i>
                    <h6 class="mt-2 fw-bold text-success">Verified &amp; Closed</h6>
                    @if($maintenance->verification_notes)
                        <p class="text-muted mb-0" style="font-size:0.85rem;">{{ $maintenance->verification_notes }}</p>
                    @endif
                </div>
            @endif

            <!-- Evidence -->
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-paperclip me-2"></i>Evidence</h6>
                @forelse($maintenance->evidence as $file)
                    <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--kp-border);">
                        <i class="bi {{ $file->isImage() ? 'bi-file-earmark-image' : 'bi-file-earmark-pdf' }}" style="font-size:1.2rem; color:var(--kp-royal);"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold" style="font-size:0.88rem;">{{ $file->original_name }}</div>
                            <small class="text-muted">
                                {{ $file->typeLabel() }} &bull; {{ $file->humanSize() }}
                                &bull; {{ $file->created_at->format('d M Y') }}
                            </small>
                        </div>
                        <a href="{{ route('maintenance.evidence.show', $file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                @empty
                    <p class="text-muted mb-0" style="font-size:0.85rem;">No evidence attached yet.</p>
                @endforelse

                <form method="POST" action="{{ route('caretaker.maintenance.evidence.store', $maintenance) }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <label class="form-label fw-semibold">Add Supporting Files</label>
                    <input type="file" name="evidence[]" class="form-control mb-2" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-upload me-1"></i> Upload</button>
                </form>
            </div>

            <!-- History ledger -->
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-clock-history me-2"></i>Activity History</h6>
                @forelse($maintenance->events as $event)
                    <div class="d-flex gap-3 py-2" style="border-bottom:1px solid var(--kp-border);">
                        <span class="kp-badge {{ $event->badgeClass() }}" style="white-space:nowrap;">{{ $event->label() }}</span>
                        <div class="flex-grow-1">
                            @if($event->message)
                                <div style="font-size:0.88rem;">{{ $event->message }}</div>
                            @endif
                            <small class="text-muted">
                                {{ $event->user?->name ?? 'System' }}
                                &bull; {{ $event->created_at->format('d M Y H:i') }}
                            </small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0" style="font-size:0.85rem;">No activity recorded yet.</p>
                @endforelse
            </div>

            <!-- Correction -->
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-flag me-2"></i>Request a Correction</h6>
                <p class="text-muted" style="font-size:0.85rem;">
                    If something on this record is inaccurate, ask the landlord to correct it. Your request is logged rather than editing the record directly.
                </p>
                <form method="POST" action="{{ route('caretaker.maintenance.correction', $maintenance) }}">
                    @csrf
                    <textarea name="message" rows="2" class="form-control mb-2" placeholder="What needs correcting?" required></textarea>
                    <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-send me-1"></i> Submit Request</button>
                </form>
            </div>
        </div>

        <div class="col-md-5">
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Tenant Info</h6>
                <div class="mb-2">
                    <small class="text-muted d-block">Name</small>
                    <span class="fw-semibold">{{ $maintenance->tenant?->full_name ?? '—' }}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Phone</small>
                    <span>{{ $maintenance->tenant?->phone ?? '—' }}</span>
                </div>
                <div>
                    <small class="text-muted d-block">Unit</small>
                    <span>{{ $maintenance->unit?->unit_number ?? '—' }}</span>
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

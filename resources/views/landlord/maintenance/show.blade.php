@extends('layouts.app')

@section('page-title', 'Maintenance Request Detail')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1">{{ $maintenance->title }}</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Submitted by {{ $maintenance->tenant?->full_name ?? '—' }}
                on {{ $maintenance->created_at->format('d M Y') }}
            </p>
        </div>
        <a href="{{ route('landlord.maintenance.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
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
            <!-- Details -->
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

                <div class="row g-3 mt-1">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Assigned to</small>
                        <span>{{ $maintenance->assignedTo?->name ?? 'Unassigned' }}</span>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Assigned</small>
                        <span>{{ $maintenance->assigned_at?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                </div>

                @if($maintenance->resolution_notes)
                    <div class="mt-3">
                        <small class="text-muted d-block">Latest Completion Notes</small>
                        <p class="mb-0">{{ $maintenance->resolution_notes }}</p>
                    </div>
                @endif

                @if($maintenance->verified_at)
                    <div class="alert alert-success mt-3 mb-0 py-2" style="font-size:0.85rem;">
                        <i class="bi bi-patch-check me-1"></i>
                        Verified by {{ $maintenance->verifiedBy?->name ?? '—' }}
                        on {{ $maintenance->verified_at->format('d M Y') }}.
                        @if($maintenance->verification_notes)<br>{{ $maintenance->verification_notes }}@endif
                    </div>
                @endif
            </div>

            <!-- Assign / deadline -->
            @if($maintenance->isOpen())
                <div class="stat-card mb-4">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-person-gear me-2"></i>Assign &amp; Schedule</h6>
                    <form method="POST" action="{{ route('landlord.maintenance.assign', $maintenance) }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Caretaker</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Unassigned --</option>
                                @foreach($caretakers as $caretaker)
                                    <option value="{{ $caretaker->id }}" {{ $maintenance->assigned_to == $caretaker->id ? 'selected' : '' }}>
                                        {{ $caretaker->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Deadline</label>
                            <input type="date" name="due_date" class="form-control"
                                   value="{{ $maintenance->due_date?->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check2"></i> Save
                            </button>
                        </div>
                    </form>
                </div>

                <div class="stat-card mb-4">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-calendar-event me-2"></i>Change Deadline</h6>
                    <form method="POST" action="{{ route('landlord.maintenance.deadline', $maintenance) }}" class="d-flex gap-2">
                        @csrf @method('PATCH')
                        <input type="date" name="due_date" class="form-control" value="{{ $maintenance->due_date?->format('Y-m-d') }}">
                        <button type="submit" class="btn btn-outline-primary" style="white-space:nowrap;">Update</button>
                    </form>
                </div>
            @endif

            <!-- Verification -->
            @if($maintenance->isAwaitingVerification())
                <div class="stat-card mb-4" style="border:1px solid rgba(233,155,32,0.4);">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-clipboard-check me-2"></i>Verify Completion</h6>
                    <p class="text-muted" style="font-size:0.85rem;">
                        The caretaker has submitted this task as complete. Confirm it to close the task, or return it for further work.
                    </p>
                    <form method="POST" action="{{ route('landlord.maintenance.verify', $maintenance) }}" class="mb-3">
                        @csrf @method('PATCH')
                        <label class="form-label fw-semibold">Verification Notes <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="verification_notes" rows="2" class="form-control mb-2" placeholder="Confirm the work is satisfactory"></textarea>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-patch-check me-2"></i> Verify &amp; Close
                        </button>
                    </form>
                    <form method="POST" action="{{ route('landlord.maintenance.return', $maintenance) }}">
                        @csrf @method('PATCH')
                        <label class="form-label fw-semibold">Return for Further Work</label>
                        <textarea name="return_reason" rows="2" class="form-control mb-2" placeholder="Explain what still needs doing" required></textarea>
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> Return to Caretaker
                        </button>
                    </form>
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
                                &bull; {{ $file->user?->name ?? '—' }}
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

                <form method="POST" action="{{ route('landlord.maintenance.evidence.store', $maintenance) }}" enctype="multipart/form-data" class="mt-3">
                    @csrf
                    <label class="form-label fw-semibold">Add Supporting Files</label>
                    <input type="file" name="evidence[]" class="form-control mb-2" multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-upload me-1"></i> Upload</button>
                </form>
            </div>

            <!-- History ledger -->
            <div class="stat-card">
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

            @if($maintenance->isTurnoverTask() && $maintenance->turnover)
                @php($turnover = $maintenance->turnover)
                <div class="stat-card mt-4">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-arrow-repeat me-2"></i>Turnover / Vacancy Recovery</h6>
                    <p class="text-muted mb-2" style="font-size:0.85rem;">
                        This repair is part of the coordinated turnover for
                        <strong>{{ $turnover->unit->unit_number ?? '—' }}</strong>.
                        Status: <span class="kp-badge {{ $turnover->statusBadgeClass() }}">{{ $turnover->statusLabel() }}</span>
                    </p>
                    <a href="{{ route('landlord.turnovers.show', $turnover) }}" class="btn btn-sm btn-outline-primary">
                        Open turnover workspace
                    </a>
                </div>
            @endif
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
                <div class="mb-2">
                    <small class="text-muted d-block">Property</small>
                    <span>{{ $maintenance->property?->name ?? '—' }}</span>
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

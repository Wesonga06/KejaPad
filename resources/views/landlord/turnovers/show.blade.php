@extends('layouts.app')

@section('page-title', 'Turnover Workspace')

@section('content')
    @php($canManage = auth()->user()->can('manage', $turnover))

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">
                Turnover — Unit {{ $turnover->unit->unit_number ?? '—' }}
                <span class="kp-badge {{ $turnover->statusBadgeClass() }} ms-2">{{ $turnover->statusLabel() }}</span>
            </h1>
            <p class="kp-page-subtitle">
                {{ $turnover->property->name ?? '—' }} · Outgoing tenant: {{ $turnover->outgoingTenant->full_name ?? '—' }}
                @if($turnover->nextTenant) · Incoming: {{ $turnover->nextTenant->full_name }} @endif
            </p>
        </div>
        <a href="{{ route('landlord.turnovers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-signpost-split me-2"></i>
        <div>
            <strong>Next action:</strong> {{ $nextAction['label'] }}
            @if($nextAction['role']) <span class="text-muted">· Owner: {{ $nextAction['role'] }}</span> @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            {{-- Checklist --}}
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-list-check me-2"></i>Turnover Checklist</h6>
                <ul class="list-unstyled mb-0">
                    @foreach($checklist as $item)
                        <li class="d-flex align-items-start mb-2">
                            <i class="bi {{ $item['complete'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-2 mt-1"></i>
                            <span>
                                {{ $item['label'] }}
                                @unless($item['mandatory'])<small class="text-muted">(optional)</small>@endunless
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Repairs --}}
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-tools me-2"></i>Repairs &amp; Tasks</h6>

                @forelse($turnover->tasks as $task)
                    <div class="border rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold">{{ $task->title }}</div>
                                <div class="text-muted" style="font-size:0.82rem;">
                                    {{ $task->categoryLabel() }} · {{ ucfirst($task->priority) }} priority
                                    @if($task->due_date) · Due {{ $task->due_date->format('d M Y') }} @endif
                                </div>
                                @if($task->description)<div class="small mt-1">{{ $task->description }}</div>@endif
                            </div>
                            <span class="badge bg-{{ $task->statusColor() }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                        </div>

                        <div class="text-muted mt-2" style="font-size:0.8rem;">
                            Assigned to: {{ $task->assignedTo->name ?? 'Unassigned' }}
                            @if($task->verified_at)
                                · <span class="text-success">Verified by {{ $task->verifiedBy->name ?? '—' }} on {{ $task->verified_at->format('d M Y') }}</span>
                            @endif
                        </div>

                        @if($canManage && ! $turnover->isClosed())
                            <div class="d-flex gap-2 mt-2 flex-wrap">
                                <form method="POST" action="{{ route('landlord.turnovers.tasks.assign', [$turnover, $task]) }}" class="d-flex gap-2">
                                    @csrf @method('PATCH')
                                    <select name="assigned_to" class="form-select form-select-sm" style="max-width:180px;">
                                        <option value="">Unassigned</option>
                                        @foreach($caretakers as $caretaker)
                                            <option value="{{ $caretaker->id }}" @selected($task->assigned_to === $caretaker->id)>{{ $caretaker->name }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary">Assign</button>
                                </form>
                                @if(in_array($task->status, ['resolved', 'closed'], true) && ! $task->verified_at)
                                    <form method="POST" action="{{ route('landlord.turnovers.tasks.verify', [$turnover, $task]) }}" class="d-flex gap-2">
                                        @csrf @method('PATCH')
                                        <input type="text" name="verification_notes" class="form-control form-control-sm" placeholder="Verification notes">
                                        <button class="btn btn-sm btn-success">Verify</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-3" style="font-size:0.9rem;">No repair tasks recorded for this turnover.</p>
                @endforelse

                @if($canManage && ! $turnover->isClosed())
                    <form method="POST" action="{{ route('landlord.turnovers.tasks.store', $turnover) }}" class="border-top pt-3">
                        @csrf
                        <div class="row g-2">
                            <div class="col-12">
                                <input type="text" name="title" class="form-control" placeholder="Repair task title" required>
                            </div>
                            <div class="col-12">
                                <textarea name="description" rows="2" class="form-control" placeholder="Describe the work required"></textarea>
                            </div>
                            <div class="col-sm-4">
                                <select name="category" class="form-select">
                                    <option value="general">General</option>
                                    <option value="plumbing">Plumbing</option>
                                    <option value="electrical">Electrical</option>
                                    <option value="security">Security</option>
                                    <option value="cleaning">Cleaning</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <select name="priority" class="form-select">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <input type="date" name="due_date" class="form-control" placeholder="Due date">
                            </div>
                            <div class="col-sm-6">
                                <select name="assigned_to" class="form-select">
                                    <option value="">Unassigned</option>
                                    @foreach($caretakers as $caretaker)
                                        <option value="{{ $caretaker->id }}">{{ $caretaker->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <button class="btn btn-primary w-100"><i class="bi bi-plus-lg me-1"></i> Add Task</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Handover & readiness --}}
            @if($canManage && ! $turnover->isClosed())
                <div class="stat-card mb-4">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-key me-2"></i>Handover</h6>
                    @if($turnover->handover_recorded_at)
                        <p class="mb-2" style="font-size:0.9rem;">
                            Recorded by {{ $turnover->handoverRecordedBy->name ?? '—' }} on {{ $turnover->handover_recorded_at->format('d M Y') }}.
                            @if($turnover->handover_notes)<br>{{ $turnover->handover_notes }}@endif
                        </p>
                    @endif
                    <form method="POST" action="{{ route('landlord.turnovers.handover', $turnover) }}">
                        @csrf
                        <textarea name="handover_notes" rows="2" class="form-control mb-2" placeholder="Keys, meter readings, condition notes">{{ $turnover->handover_notes }}</textarea>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="keys_returned" value="1" id="keys_returned">
                            <label class="form-check-label" for="keys_returned">Keys returned</label>
                        </div>
                        <button class="btn btn-outline-primary">Record Handover</button>
                    </form>
                </div>

                <div class="stat-card mb-4">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-door-open me-2"></i>Readiness</h6>
                    @if($turnover->readiness_confirmed_at)
                        <div class="alert alert-success py-2" style="font-size:0.85rem;">
                            Confirmed ready by {{ $turnover->readinessConfirmedBy->name ?? '—' }} on {{ $turnover->readiness_confirmed_at->format('d M Y') }}.
                            @if($turnover->readiness_notes)<br>{{ $turnover->readiness_notes }}@endif
                        </div>
                    @endif
                    <form method="POST" action="{{ route('landlord.turnovers.ready', $turnover) }}" class="mb-3">
                        @csrf
                        <input type="text" name="readiness_notes" class="form-control mb-2" placeholder="Readiness notes (optional)">
                        <button class="btn btn-primary">Confirm Unit Ready for Letting</button>
                    </form>
                    <form method="POST" action="{{ route('landlord.turnovers.onboarding', $turnover) }}">
                        @csrf
                        <button class="btn btn-success" {{ $turnover->isReadyForLetting() ? '' : 'disabled' }}>
                            <i class="bi bi-person-plus me-1"></i> Begin New Tenancy
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            {{-- Overview --}}
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Turnover Overview</h6>
                <div class="mb-2">
                    <small class="text-muted d-block">Unit</small>
                    <span class="fw-semibold">{{ $turnover->unit->unit_number ?? '—' }}</span>
                    <span class="badge bg-light text-dark border ms-1">{{ $turnover->unit->status ?? '—' }}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Move-out Request</small>
                    @if($turnover->moveOutRequest)
                        <a href="{{ route('landlord.move-outs.show', $turnover->moveOutRequest) }}">
                            View request · {{ ucfirst(str_replace('_', ' ', $turnover->moveOutRequest->status)) }}
                        </a>
                        <div class="text-muted" style="font-size:0.8rem;">
                            Proposed {{ optional($turnover->moveOutRequest->proposed_move_out_date)->format('d M Y') ?? '—' }}
                        </div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Move-out Inspection</small>
                    @if($inspection)
                        <a href="{{ route('landlord.passport.inspections.show', $inspection) }}">
                            View inspection · {{ $inspection->isSubmitted() ? 'Submitted' : 'Draft' }}
                        </a>
                    @else
                        <span class="text-muted">Not recorded</span>
                    @endif
                </div>
                <div>
                    <small class="text-muted d-block">Deposit Settlement</small>
                    @if($settlement)
                        <a href="{{ route('landlord.settlements.show', $settlement) }}">
                            View settlement · {{ $settlement->statusLabel() }}
                        </a>
                    @elseif($deposit)
                        <span class="text-muted">Deposit held — settlement not started</span>
                    @else
                        <span class="text-muted">No deposit</span>
                    @endif
                </div>
            </div>

            {{-- Incoming tenancy --}}
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Incoming Tenancy</h6>
                @if($turnover->nextTenant)
                    <div class="mb-2">
                        <small class="text-muted d-block">Incoming Tenant</small>
                        <span class="fw-semibold">{{ $turnover->nextTenant->full_name }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Status</small>
                        <span class="badge {{ $turnover->nextTenant->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $turnover->nextTenant->is_active ? 'Active' : 'Pending' }}
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Move-in Inspection</small>
                        @if($incomingInspection)
                            <a href="{{ route('landlord.passport.inspections.show', $incomingInspection) }}">
                                View inspection · {{ $incomingInspection->isSubmitted() ? 'Submitted' : 'Draft' }}
                            </a>
                        @else
                            <span class="text-muted">Not created</span>
                        @endif
                    </div>
                @else
                    <p class="text-muted mb-2" style="font-size:0.85rem;">No incoming tenancy linked yet.</p>
                @endif

                @if($canManage && $turnover->next_tenant_id && ! $incomingInspection)
                    <form method="POST" action="{{ route('landlord.turnovers.incoming-inspection', $turnover) }}" class="mt-3">
                        @csrf
                        <button class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-clipboard-plus me-1"></i> Create Move-in Inspection
                        </button>
                    </form>
                @endif
            </div>

            {{-- History --}}
            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-clock-history me-2"></i>Turnover History</h6>
                <ul class="list-unstyled mb-0">
                    @forelse($turnover->events as $event)
                        <li class="d-flex mb-3">
                            <div class="me-2"><i class="bi bi-dot"></i></div>
                            <div>
                                <div class="fw-semibold" style="font-size:0.85rem;">{{ $event->label() }}</div>
                                @if($event->message)<div class="text-muted" style="font-size:0.8rem;">{{ $event->message }}</div>@endif
                                <div class="text-muted" style="font-size:0.75rem;">
                                    {{ $event->created_at->format('d M Y H:i') }}
                                    @if($event->user) · {{ $event->user->name }} @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted">No history yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

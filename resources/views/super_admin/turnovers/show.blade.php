@extends('layouts.app')

@section('page-title', 'Turnover Oversight')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">
                Turnover — Unit {{ $turnover->unit->unit_number ?? '—' }}
                <span class="kp-badge {{ $turnover->statusBadgeClass() }} ms-2">{{ $turnover->statusLabel() }}</span>
            </h1>
            <p class="kp-page-subtitle">
                {{ $turnover->property->name ?? '—' }} · Landlord: {{ $turnover->property->landlord->name ?? '—' }}
            </p>
        </div>
        <a href="{{ route('super_admin.turnovers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-info-circle me-2"></i>
        <div>Read-only oversight view. <strong>Next action:</strong> {{ $nextAction['label'] }}
            @if($nextAction['role']) <span class="text-muted">· Owner: {{ $nextAction['role'] }}</span> @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-list-check me-2"></i>Turnover Checklist</h6>
                <ul class="list-unstyled mb-0">
                    @foreach($checklist as $item)
                        <li class="d-flex align-items-start mb-2">
                            <i class="bi {{ $item['complete'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }} me-2 mt-1"></i>
                            <span>{{ $item['label'] }} @unless($item['mandatory'])<small class="text-muted">(optional)</small>@endunless</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-tools me-2"></i>Repair Tasks</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr><th>Task</th><th>Assigned</th><th>Status</th><th>Verified</th></tr>
                        </thead>
                        <tbody>
                            @forelse($turnover->tasks as $task)
                                <tr>
                                    <td>{{ $task->title }}</td>
                                    <td>{{ $task->assignedTo->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $task->statusColor() }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span></td>
                                    <td>{{ $task->verified_at ? $task->verified_at->format('d M Y') : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No repair tasks.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Overview</h6>
                <div class="mb-2"><small class="text-muted d-block">Outgoing Tenant</small><span>{{ $turnover->outgoingTenant->full_name ?? '—' }}</span></div>
                <div class="mb-2"><small class="text-muted d-block">Incoming Tenant</small><span>{{ $turnover->nextTenant->full_name ?? '—' }}</span></div>
                <div class="mb-2"><small class="text-muted d-block">Move-out Date</small><span>{{ optional($turnover->moveOutRequest?->proposed_move_out_date)->format('d M Y') ?? '—' }}</span></div>
                <div class="mb-2"><small class="text-muted d-block">Readiness Confirmed</small>
                    <span>{{ $turnover->readiness_confirmed_at ? $turnover->readiness_confirmed_at->format('d M Y') : 'Not yet' }}</span></div>
                <div><small class="text-muted d-block">Deposit Settlement</small>
                    <span>{{ $turnover->moveOutRequest?->settlement?->statusLabel() ?? 'Not started' }}</span></div>
            </div>

            <div class="stat-card">
                <h6 class="fw-bold mb-3 pb-2 border-bottom"><i class="bi bi-clock-history me-2"></i>History</h6>
                <ul class="list-unstyled mb-0">
                    @forelse($turnover->events as $event)
                        <li class="d-flex mb-3">
                            <div class="me-2"><i class="bi bi-dot"></i></div>
                            <div>
                                <div class="fw-semibold" style="font-size:0.85rem;">{{ $event->label() }}</div>
                                @if($event->message)<div class="text-muted" style="font-size:0.8rem;">{{ $event->message }}</div>@endif
                                <div class="text-muted" style="font-size:0.75rem;">
                                    {{ $event->created_at->format('d M Y H:i') }} @if($event->user) · {{ $event->user->name }} @endif
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

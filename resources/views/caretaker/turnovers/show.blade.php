@extends('layouts.app')

@section('page-title', 'Turnover Detail')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">
                Turnover — Unit {{ $turnover->unit->unit_number ?? '—' }}
                <span class="kp-badge {{ $turnover->statusBadgeClass() }} ms-2">{{ $turnover->statusLabel() }}</span>
            </h1>
            <p class="kp-page-subtitle">{{ $turnover->property->name ?? '—' }}</p>
        </div>
        <a href="{{ route('caretaker.turnovers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-signpost-split me-2"></i>
        <div><strong>Next action:</strong> {{ $nextAction['label'] }}
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
                @forelse($turnover->tasks as $task)
                    <div class="d-flex justify-content-between align-items-center border rounded-3 p-3 mb-2">
                        <div>
                            <div class="fw-semibold">{{ $task->title }}</div>
                            <div class="text-muted" style="font-size:0.8rem;">
                                {{ $task->categoryLabel() }} · {{ ucfirst($task->priority) }}
                                @if($task->assigned_to === auth()->id()) · <span class="text-primary">Assigned to you</span> @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $task->statusColor() }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                            <div class="mt-1">
                                <a href="{{ route('caretaker.maintenance.show', $task) }}" class="btn btn-sm btn-outline-primary">Open</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No repair tasks recorded.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-5">
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Turnover Overview</h6>
                <div class="mb-2"><small class="text-muted d-block">Unit</small><span class="fw-semibold">{{ $turnover->unit->unit_number ?? '—' }}</span></div>
                <div class="mb-2"><small class="text-muted d-block">Outgoing Tenant</small><span>{{ $turnover->outgoingTenant->full_name ?? '—' }}</span></div>
                <div><small class="text-muted d-block">Proposed Move-out Date</small><span>{{ optional($turnover->moveOutRequest?->proposed_move_out_date)->format('d M Y') ?? '—' }}</span></div>
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
                                <div class="text-muted" style="font-size:0.75rem;">{{ $event->created_at->format('d M Y H:i') }}</div>
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

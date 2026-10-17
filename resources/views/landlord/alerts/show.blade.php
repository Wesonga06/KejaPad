@extends('layouts.app')

@section('page-title', 'Alert')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">{{ $alert->title }}</h1>
            <p class="kp-page-subtitle">
                <span class="kp-badge {{ $alert->severityBadgeClass() }}">{{ $alert->severityLabel() }}</span>
                <span class="kp-badge {{ $alert->statusBadgeClass() }}">{{ $alert->statusLabel() }}</span>
                <span class="text-muted ms-2">{{ $alert->typeLabel() }}</span>
            </p>
        </div>
        <a href="{{ route('landlord.alerts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back to warnings
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="kp-card mb-4">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">What this means</h2>
                </div>
                <div class="p-3 pt-0">
                    <p class="mb-3">{{ $alert->reason }}</p>
                    @if($alert->recommended_action)
                        <div class="alert alert-info mb-0">
                            <strong class="d-block mb-1"><i class="bi bi-lightbulb me-1"></i> Suggested next step</strong>
                            {{ $alert->recommended_action }}
                        </div>
                    @endif
                </div>
            </div>

            @if($alert->isDismissed() && $alert->dismissed_reason)
                <div class="alert alert-warning">
                    <strong class="d-block mb-1">Dismissed</strong>
                    {{ $alert->dismissed_reason }}
                    @if($alert->dismissedBy)
                        <span class="text-muted d-block mt-1" style="font-size:0.82rem;">by {{ $alert->dismissedBy->name }} on {{ optional($alert->dismissed_at)->format('d M Y') }}</span>
                    @endif
                </div>
            @endif

            @if($alert->isAcknowledged() && $alert->acknowledgedBy)
                <div class="alert alert-light border">
                    Acknowledged by <strong>{{ $alert->acknowledgedBy->name }}</strong>
                    on {{ optional($alert->acknowledged_at)->format('d M Y') }}.
                </div>
            @endif

            @if($events->isNotEmpty())
                <div class="kp-card">
                    <div class="kp-card-header">
                        <h2 class="kp-card-title">History</h2>
                    </div>
                    <ul class="list-unstyled mb-0 p-3">
                        @foreach($events as $event)
                            <li class="d-flex gap-3 {{ ! $loop->last ? 'pb-3 mb-3 border-bottom' : '' }}">
                                <div class="text-muted" style="font-size:0.8rem; min-width:120px;">
                                    {{ optional($event->created_at)->format('d M Y H:i') }}
                                </div>
                                <div>
                                    <strong style="font-size:0.9rem;">{{ $event->label() }}</strong>
                                    @if($event->message)
                                        <div class="text-muted" style="font-size:0.85rem;">{{ $event->message }}</div>
                                    @endif
                                    @if($event->user)
                                        <div class="text-muted" style="font-size:0.8rem;">{{ $event->user->name }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="kp-card mb-4">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Details</h2>
                </div>
                <div class="p-3 pt-0">
                    <dl class="row mb-0" style="font-size:0.88rem;">
                        <dt class="col-5 text-muted fw-normal">Property</dt>
                        <dd class="col-7 text-end fw-semibold">{{ $alert->property->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Unit</dt>
                        <dd class="col-7 text-end">{{ $alert->unit->unit_number ?? '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">Tenant</dt>
                        <dd class="col-6 text-end">{{ $alert->tenant->full_name ?? '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">Detected</dt>
                        <dd class="text-end">{{ optional($alert->detected_at)->format('d M Y') ?? '—' }}</dd>
                        <div class="col-12"><hr class="my-2"></div>
                        <dt class="col-6 text-muted fw-normal">Last checked</dt>
                        <dd class="col-6 text-end">{{ optional($alert->last_seen_at)->format('d M Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            @can('manage', $alert)
                @if($alert->isUnresolved() || $alert->isDismissed())
                    <div class="kp-card">
                    <div class="kp-card-header">
                        <h2 class="kp-card-title">Actions</h2>
                    </div>
                    <div class="p-3 pt-0 d-grid gap-2">
                        @if($alert->status === \App\Models\PropertyAlert::STATUS_ACTIVE)
                            <form method="POST" action="{{ route('landlord.alerts.acknowledge', $alert) }}">
                                @csrf
                                <textarea name="note" class="form-control mb-2" rows="2" placeholder="Optional note…"></textarea>
                                <button class="btn btn-outline-primary w-100">
                                    <i class="bi bi-eye me-1"></i> Acknowledge
                                </button>
                            </form>
                        @endif

                        @if(! $alert->isResolved())
                            <form method="POST" action="{{ route('landlord.alerts.resolve', $alert) }}">
                                @csrf
                                <textarea name="note" class="form-control mb-2" rows="2" placeholder="Optional note…"></textarea>
                                <button class="btn btn-outline-success w-100">
                                    <i class="bi bi-check2 me-1"></i> Resolve
                                </button>
                            </form>
                        @endif

                        @if(! $alert->isDismissed())
                            @if($alert->canBeDismissed())
                                <form method="POST" action="{{ route('landlord.alerts.dismiss', $alert) }}">
                                    @csrf
                                    <input type="text" name="dismissed_reason" class="form-control mb-2" placeholder="Reason for dismissing (required)" required>
                                    <button class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-x-circle me-1"></i> Dismiss
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-warning mb-0" style="font-size:0.82rem;">
                                    Critical warnings can't be dismissed — acknowledge or resolve once handled.
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endif
            @endcan
        </div>
    </div>
@endsection

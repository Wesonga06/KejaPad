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
        <a href="{{ route('super_admin.alerts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
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
            <div class="kp-card">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Details</h2>
                </div>
                <div class="p-3 pt-0">
                    <dl class="row mb-0">
                        <dt class="col-6 text-muted fw-normal">Landlord</dt>
                        <dd class="col-6 text-end">{{ $alert->property->landlord->name ?? '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">Property</dt>
                        <dd class="col-6 text-end">{{ $alert->property->name ?? '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">Unit</dt>
                        <dd class="col-6 text-end">{{ $alert->unit->unit_number ?? '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">Tenant</dt>
                        <dd class="col-6 text-end">{{ $alert->tenant->full_name ?? '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">Detected</dt>
                        <dd class="col-6 text-end">{{ optional($alert->detected_at)->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-6 text-muted fw-normal">Last checked</dt>
                        <dd class="col-6 text-end">{{ optional($alert->last_seen_at)->format('d M Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

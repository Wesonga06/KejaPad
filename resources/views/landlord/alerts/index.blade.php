@extends('layouts.app')

@section('page-title', 'Alerts')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Early Warnings</h1>
            <p class="kp-page-subtitle">Rule-based signals that flag things worth your attention — overdue rent, stalled repairs, expiring tenancies and slow-to-fill units.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-bell"></i></div></div>
                <div class="kp-kpi-value">{{ $summary['total'] }}</div>
                <p class="kp-kpi-label">Open Warnings</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-exclamation-octagon"></i></div></div>
                <div class="kp-kpi-value">{{ $summary['critical'] }}</div>
                <p class="kp-kpi-label">Critical</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-exclamation-triangle"></i></div></div>
                <div class="kp-kpi-value">{{ $summary['warning'] }}</div>
                <p class="kp-kpi-label">Needs Attention</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-info-circle"></i></div></div>
                <div class="kp-kpi-value">{{ $summary['info'] }}</div>
                <p class="kp-kpi-label">Information</p>
            </div>
        </div>
    </div>

    @if($escalatedCount > 0)
        <div class="kp-card mb-4" style="border-left:4px solid var(--kp-danger);">
            <div class="kp-card-header">
                <div>
                    <h2 class="kp-card-title"><i class="bi bi-tools me-2"></i>Escalated Maintenance</h2>
                    <p class="kp-card-subtitle">{{ $escalatedCount }} overdue assigned task(s) past their deadline.</p>
                </div>
                <a href="{{ route('landlord.maintenance.index', ['scope' => 'overdue']) }}" class="btn btn-sm btn-outline-danger">
                    Review Overdue Tasks
                </a>
            </div>
            @foreach($escalatedTasks as $task)
                <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--kp-border);">
                    <span class="kp-badge kp-badge-danger">Overdue</span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold" style="font-size:0.88rem;">{{ $task->title }}</div>
                        <small class="text-muted">
                            {{ $task->property?->name ?? '—' }}
                            @if($task->unit) &bull; Unit {{ $task->unit->unit_number }}@endif
                            &bull; {{ $task->assignedTo?->name ?? 'Unassigned' }}
                            &bull; due {{ optional($task->due_date)->format('d M Y') }}
                        </small>
                    </div>
                    <a href="{{ route('landlord.maintenance.show', $task) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
            @endforeach
            @if($escalatedCount > $escalatedTasks->count())
                <p class="text-muted mb-0 mt-2" style="font-size:0.8rem;">
                    Showing {{ $escalatedTasks->count() }} of {{ $escalatedCount }}.
                </p>
            @endif
        </div>
    @endif

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Warnings</h2>
            <form method="GET" action="{{ route('landlord.alerts.index') }}" class="d-flex gap-2 flex-wrap">
                <select name="status" class="form-select form-select-sm" style="max-width:170px;" onchange="this.form.submit()">
                    <option value="open" @selected(request('status', 'open') === 'open')>Open</option>
                    @foreach(\App\Models\PropertyAlert::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="property_id" class="form-select form-select-sm" style="max-width:190px;" onchange="this.form.submit()">
                    <option value="">All properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected((int) request('property_id') === $property->id)>{{ $property->name }}</option>
                    @endforeach
                </select>
                <select name="severity" class="form-select form-select-sm" style="max-width:170px;" onchange="this.form.submit()">
                    <option value="">All severities</option>
                    @foreach(\App\Models\PropertyAlert::severities() as $value => $label)
                        <option value="{{ $value }}" @selected(request('severity') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="type" class="form-select form-select-sm" style="max-width:210px;" onchange="this.form.submit()">
                    <option value="">All types</option>
                    @foreach(\App\Models\PropertyAlert::types() as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>

        @if($alerts->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-check2-circle"></i>
                <p class="mb-0">Nothing needs your attention right now. Warnings appear here as conditions change.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Severity</th>
                            <th>Warning</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Detected</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            <tr>
                                <td><span class="kp-badge {{ $alert->severityBadgeClass() }}">{{ $alert->severityLabel() }}</span></td>
                                <td style="font-size:0.85rem;">
                                    <span class="fw-semibold">{{ $alert->title }}</span>
                                    <span class="text-muted d-block">{{ $alert->typeLabel() }}</span>
                                </td>
                                <td style="font-size:0.85rem;">
                                    <span class="fw-semibold">{{ $alert->property->name ?? '—' }}</span>
                                    @if($alert->unit)
                                        <span class="text-muted d-block">Unit {{ $alert->unit->unit_number }}</span>
                                    @elseif($alert->tenant)
                                        <span class="text-muted d-block">{{ $alert->tenant->full_name }}</span>
                                    @endif
                                </td>
                                <td><span class="kp-badge {{ $alert->statusBadgeClass() }}">{{ $alert->statusLabel() }}</span></td>
                                <td>{{ optional($alert->detected_at)->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('landlord.alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $alerts->links() }}</div>
        @endif
    </div>
@endsection

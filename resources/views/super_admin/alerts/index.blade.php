@extends('layouts.app')

@section('page-title', 'Alerts')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Early-Warning Oversight</h1>
            <p class="kp-page-subtitle">Platform-wide, read-only view of rule-based warnings raised across all landlords.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-bell"></i></div></div>
                <div class="kp-kpi-value">{{ $summary['open'] }}</div>
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
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-buildings"></i></div></div>
                <div class="kp-kpi-value">{{ $summary['property_count'] }}</div>
                <p class="kp-kpi-label">Properties Affected</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Warnings</h2>
            <form method="GET" action="{{ route('super_admin.alerts.index') }}" class="d-flex gap-2 flex-wrap">
                <select name="status" class="form-select form-select-sm" style="max-width:170px;" onchange="this.form.submit()">
                    <option value="open" @selected(request('status', 'open') === 'open')>Open</option>
                    @foreach(\App\Models\PropertyAlert::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
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
                <p class="mb-0">No warnings match the current filters.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Severity</th>
                            <th>Warning</th>
                            <th>Landlord</th>
                            <th>Property / Unit</th>
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
                                <td>{{ $alert->property->landlord->name ?? '—' }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $alert->property->name ?? '—' }}
                                    @if($alert->unit)
                                        <span class="text-muted">/ {{ $alert->unit->unit_number }}</span>
                                    @endif
                                </td>
                                <td><span class="kp-badge {{ $alert->statusBadgeClass() }}">{{ $alert->statusLabel() }}</span></td>
                                <td>{{ optional($alert->detected_at)->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('super_admin.alerts.show', $alert) }}" class="btn btn-sm btn-outline-primary">View</a>
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

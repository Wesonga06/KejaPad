@extends('layouts.app')

@section('page-title', 'Turnovers')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Turnovers &amp; Vacancy Recovery</h1>
            <p class="kp-page-subtitle">Coordinate every unit's move-out, inspection, repairs, readiness and the next tenancy from one workspace.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-arrow-repeat"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['open'] }}</div>
                <p class="kp-kpi-label">Turnovers In Progress</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-tools"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['repairs'] }}</div>
                <p class="kp-kpi-label">Repairs Required</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-patch-question"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['awaiting_verification'] }}</div>
                <p class="kp-kpi-label">Awaiting Verification</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-door-open"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['ready'] }}</div>
                <p class="kp-kpi-label">Ready for Letting</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Turnovers</h2>
            <form method="GET" action="{{ route('landlord.turnovers.index') }}" class="d-flex gap-2 flex-wrap">
                <select name="property_id" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                    <option value="">All properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected((int) request('property_id') === $property->id)>{{ $property->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" style="max-width:220px;" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach(\App\Models\Turnover::statuses() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>

        @if($turnovers->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-arrow-repeat"></i>
                <p class="mb-0">No turnovers yet. A turnover workspace opens automatically when a tenant gives notice to vacate.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Outgoing Tenant</th>
                            <th>Move-out Date</th>
                            <th>Incoming Tenant</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($turnovers as $turnover)
                            <tr>
                                <td style="font-size:0.85rem;">
                                    <span class="fw-semibold">{{ $turnover->unit->unit_number ?? '—' }}</span>
                                    <span class="text-muted d-block">{{ $turnover->property->name ?? '—' }}</span>
                                </td>
                                <td>{{ $turnover->outgoingTenant->full_name ?? '—' }}</td>
                                <td>{{ optional($turnover->moveOutRequest?->proposed_move_out_date)->format('d M Y') ?? '—' }}</td>
                                <td>{{ $turnover->nextTenant->full_name ?? '—' }}</td>
                                <td><span class="kp-badge {{ $turnover->statusBadgeClass() }}">{{ $turnover->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('landlord.turnovers.show', $turnover) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $turnovers->links() }}</div>
        @endif
    </div>
@endsection

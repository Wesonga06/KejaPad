@extends('layouts.app')

@section('page-title', 'Rent Arrears')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Rent Arrears</h1>
            <p class="kp-page-subtitle">Colour-coded arrears status derived from each tenant's rent ledger.</p>
        </div>
        <div class="kp-page-actions">
            <form method="GET" action="{{ route('landlord.arrears.index') }}">
                <select name="property_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected(request('property_id') == $property->id)>
                            {{ $property->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon red"><i class="bi bi-exclamation-octagon"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem; color:var(--kp-danger);">KES {{ number_format($totalArrears, 0) }}</div>
                <p class="kp-kpi-label">Total Arrears</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon red"><i class="bi bi-hourglass-bottom"></i></div></div>
                <div class="kp-kpi-value">{{ $counts['red'] }}</div>
                <p class="kp-kpi-label">Overdue</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-arrow-up-right-circle"></i></div></div>
                <div class="kp-kpi-value">{{ $counts['orange'] }}</div>
                <p class="kp-kpi-label">Escalated</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-bell"></i></div></div>
                <div class="kp-kpi-value">{{ $counts['yellow'] }}</div>
                <p class="kp-kpi-label">Further Action</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        @if($rows->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-emoji-smile"></i>
                <p class="mb-0">No tenants are in arrears right now.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end">Arrears</th>
                            <th class="text-center">Days Overdue</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php $s = $row->summary; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $row->tenant->full_name }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $row->tenant->property->name }}
                                    <span class="text-muted">/ {{ $row->tenant->unit->unit_number }}</span>
                                </td>
                                <td class="text-end">KES {{ number_format($s['outstanding'], 2) }}</td>
                                <td class="text-end fw-semibold" style="color:var(--kp-danger);">KES {{ number_format($s['arrears'], 2) }}</td>
                                <td class="text-center">{{ $s['max_overdue_days'] }}</td>
                                <td>
                                    <span class="badge rounded-pill" style="background:{{ $s['status']['color'] }}; color:#fff;">
                                        {{ $s['status']['label'] }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('landlord.tenants.show', $row->tenant) }}"
                                       class="btn btn-sm btn-outline-primary">View Account</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

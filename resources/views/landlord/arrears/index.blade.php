@extends('layouts.app')

@section('page-title', 'Rent Arrears')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}" class="nav-link">
        <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}" class="nav-link">
        <i class="bi bi-cash-coin"></i> Rent
    </a>
    <a href="{{ route('landlord.arrears.index') }}" class="nav-link active">
        <i class="bi bi-exclamation-octagon"></i> Arrears
    </a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('landlord.reports.index') }}" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1">Rent Arrears</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Colour-coded arrears status derived from each tenant's rent ledger.
            </p>
        </div>
        <form method="GET" action="{{ route('landlord.arrears.index') }}" class="d-flex gap-2">
            <select name="property_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All properties</option>
                @foreach($properties as $property)
                    <option value="{{ $property->id }}" @selected(request('property_id') == $property->id)>
                        {{ $property->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">KES {{ number_format($totalArrears, 2) }}</h4>
                <small class="text-muted">Total Arrears</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold mb-0" style="color:#dc3545;">{{ $counts['red'] }}</h4>
                <small class="text-muted">Overdue</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold mb-0" style="color:#fd7e14;">{{ $counts['orange'] }}</h4>
                <small class="text-muted">Escalated</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold mb-0" style="color:#b8860b;">{{ $counts['yellow'] }}</h4>
                <small class="text-muted">Further Action</small>
            </div>
        </div>
    </div>

    <div class="stat-card">
        @if($rows->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-emoji-smile" style="font-size:3rem; color:#198754;"></i>
                <p class="mt-3 text-muted mb-0">No tenants are in arrears. </p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end">Arrears</th>
                            <th class="text-center">Days Overdue</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            @php $s = $row->summary; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $row->tenant->full_name }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $row->tenant->property->name }} / {{ $row->tenant->unit->unit_number }}
                                </td>
                                <td class="text-end">KES {{ number_format($s['outstanding'], 2) }}</td>
                                <td class="text-end text-danger fw-semibold">KES {{ number_format($s['arrears'], 2) }}</td>
                                <td class="text-center">{{ $s['max_overdue_days'] }}</td>
                                <td>
                                    <span class="badge rounded-pill" style="background:{{ $s['status']['color'] }};">
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

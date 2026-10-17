@extends('layouts.app')

@section('page-title', 'Property Passports')

@section('content')
<div class="kp-page-header">
    <div>
        <h1 class="kp-page-title">Property Passports</h1>
        <p class="kp-page-subtitle">Units across the properties you manage.</p>
    </div>
</div>

@if($units->isEmpty())
    <div class="kp-card">
        <div class="kp-empty">
            <i class="bi bi-folder2-open"></i>
            <p class="mt-3 mb-0">No units are assigned to you yet.</p>
        </div>
    </div>
@else
    <div class="kp-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Unit</th>
                        <th>Passport code</th>
                        <th>Occupancy</th>
                        <th>Tenant</th>
                        <th>Inspections</th>
                        <th>Open maintenance</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($units as $unit)
                        <tr>
                            <td>{{ $unit->property->name ?? '—' }}</td>
                            <td class="fw-semibold">{{ $unit->unit_number }}</td>
                            <td><span class="text-muted">{{ $unit->passportCode() }}</span></td>
                            <td><span class="kp-badge {{ $unit->statusBadgeClass() }}">{{ ucfirst($unit->status) }}</span></td>
                            <td>{{ $unit->tenant->full_name ?? '—' }}</td>
                            <td>{{ $unit->inspections_count }}</td>
                            <td>
                                @if($unit->open_maintenance_count > 0)
                                    <span class="kp-badge kp-badge-warning">{{ $unit->open_maintenance_count }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('caretaker.passport.show', $unit) }}" class="btn btn-sm btn-primary">
                                    Open Passport <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

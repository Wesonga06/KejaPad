@extends('layouts.app')

@section('page-title', 'Property Passports')

@section('content')
<div class="kp-page-header">
    <div>
        <h1 class="kp-page-title">Property Passports</h1>
        <p class="kp-page-subtitle">Read-only oversight of every unit on the platform.</p>
    </div>
    <div class="kp-page-actions">
        <form method="GET" action="{{ route('super_admin.passports.index') }}" class="d-flex gap-2">
            <input type="search" name="q" class="form-control" placeholder="Search unit or property" value="{{ request('q') }}">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

<div class="alert alert-info d-flex align-items-center">
    <i class="bi bi-shield-lock me-2"></i>Administrative oversight view. Records are read-only from here.
</div>

@if($units->isEmpty())
    <div class="kp-card">
        <div class="kp-empty">
            <i class="bi bi-folder2-open"></i>
            <p class="mt-3 mb-0">No units found.</p>
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
                            <td class="text-end">
                                <a href="{{ route('super_admin.passports.show', $unit) }}" class="btn btn-sm btn-outline-primary">
                                    View <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($units->hasPages())
        <div class="mt-3">{{ $units->links() }}</div>
    @endif
@endif
@endsection

@extends('layouts.app')

@section('page-title', 'Turnovers')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Turnover Oversight</h1>
            <p class="kp-page-subtitle">Platform-wide, read-only view of tenant turnovers and vacancy recovery across all landlords.</p>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Turnovers</h2>
        </div>

        @if($turnovers->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-arrow-repeat"></i>
                <p class="mb-0">No turnovers recorded yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Landlord</th>
                            <th>Property / Unit</th>
                            <th>Outgoing Tenant</th>
                            <th>Incoming Tenant</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($turnovers as $turnover)
                            <tr>
                                <td class="fw-semibold">{{ $turnover->property->landlord->name ?? '—' }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $turnover->property->name ?? '—' }}
                                    <span class="text-muted">/ {{ $turnover->unit->unit_number ?? '—' }}</span>
                                </td>
                                <td>{{ $turnover->outgoingTenant->full_name ?? '—' }}</td>
                                <td>{{ $turnover->nextTenant->full_name ?? '—' }}</td>
                                <td><span class="kp-badge {{ $turnover->statusBadgeClass() }}">{{ $turnover->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('super_admin.turnovers.show', $turnover) }}" class="btn btn-sm btn-outline-primary">View</a>
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

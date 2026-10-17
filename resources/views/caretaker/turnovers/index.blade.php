@extends('layouts.app')

@section('page-title', 'Turnovers')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Unit Turnovers</h1>
            <p class="kp-page-subtitle">Turnovers for the properties you manage, and any turnover repair assigned to you.</p>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Turnovers</h2>
        </div>

        @if($turnovers->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-arrow-repeat"></i>
                <p class="mb-0">No turnovers for your properties yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Outgoing Tenant</th>
                            <th>Move-out Date</th>
                            <th>Assigned to Me</th>
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
                                <td><span class="badge bg-light text-dark border">{{ $turnover->assigned_task_count }}</span></td>
                                <td><span class="kp-badge {{ $turnover->statusBadgeClass() }}">{{ $turnover->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('caretaker.turnovers.show', $turnover) }}" class="btn btn-sm btn-outline-primary">Open</a>
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

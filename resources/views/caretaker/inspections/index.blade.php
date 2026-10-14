@extends('layouts.app')

@section('page-title', 'Move-out Inspections')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Move-out Inspections</h1>
            <p class="kp-page-subtitle">Inspections assigned to you for tenants who are moving out.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-4">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-clipboard-check"></i></div></div>
                <div class="kp-kpi-value">{{ $pending }}</div>
                <p class="kp-kpi-label">Pending Inspections</p>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check2-circle"></i></div></div>
                <div class="kp-kpi-value">{{ $submitted }}</div>
                <p class="kp-kpi-label">Submitted</p>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-list-check"></i></div></div>
                <div class="kp-kpi-value">{{ $inspections->total() }}</div>
                <p class="kp-kpi-label">Total</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Assigned inspections</h2>
            <form method="GET" action="{{ route('caretaker.inspections.index') }}">
                <select name="status" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <option value="assigned" @selected(request('status') === 'assigned')>Assigned</option>
                    <option value="in_progress" @selected(request('status') === 'in_progress')>In Progress</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>Submitted</option>
                </select>
            </form>
        </div>

        @if($inspections->isEmpty())
            <div class="kp-empty"><i class="bi bi-clipboard-check"></i><p class="mb-0">No inspections assigned to you.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Tenant</th><th>Property / Unit</th><th>Scheduled</th><th>Status</th><th class="text-end"></th></tr>
                    </thead>
                    <tbody>
                        @foreach($inspections as $inspection)
                            <tr>
                                <td class="fw-semibold">{{ $inspection->moveOutRequest->tenant->full_name ?? '—' }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $inspection->moveOutRequest->property->name ?? '—' }}
                                    <span class="text-muted">/ {{ $inspection->moveOutRequest->unit->unit_number ?? '—' }}</span>
                                </td>
                                <td class="text-muted">{{ optional($inspection->scheduled_at)->format('d M Y H:i') ?? '—' }}</td>
                                <td><span class="kp-badge {{ $inspection->statusBadgeClass() }}">{{ $inspection->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('caretaker.inspections.show', $inspection) }}" class="btn btn-sm btn-outline-primary">
                                        {{ $inspection->isSubmitted() ? 'View' : 'Complete' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $inspections->links() }}</div>
        @endif
    </div>
@endsection

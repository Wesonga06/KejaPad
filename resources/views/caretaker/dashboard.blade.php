@extends('layouts.app')

@section('page-title', 'Caretaker Dashboard')


@section('content')
    <div class="p-4 mb-4 rounded-3"
         style="background: linear-gradient(135deg, #168653, #F4C542); color: white;">
        <h4 class="fw-bold mb-1">Welcome, {{ auth()->user()->name }} 👋</h4>
        <p class="mb-0 opacity-75">Here are the maintenance requests for your properties.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Pending Inspections</p>
                        <h3 class="stat-value">{{ $pendingInspections }}</h3>
                    </div>
                    <div class="stat-icon" style="background:rgba(220,69,69,0.12);">
                        <i class="bi bi-clipboard-x" style="color:#DC4545;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Pending</p>
                        <h3 class="stat-value">{{ $pendingCount }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#f0f0f0;">
                        <i class="bi bi-hourglass-split" style="color:#6c757d;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">In Progress</p>
                        <h3 class="stat-value">{{ $inProgressCount }}</h3>
                    </div>
                    <div class="stat-icon" style="background:rgba(233,155,32,0.14);">
                        <i class="bi bi-tools" style="color:#E99B20;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Resolved</p>
                        <h3 class="stat-value">{{ $resolvedCount }}</h3>
                    </div>
                    <div class="stat-icon" style="background:rgba(22,134,83,0.12);">
                        <i class="bi bi-check-circle" style="color:#168653;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total</p>
                        <h3 class="stat-value">{{ $totalCount }}</h3>
                    </div>
                    <div class="stat-icon" style="background:rgba(89,38,200,0.08);">
                        <i class="bi bi-clipboard-check" style="color:#5926C8;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kp-card mb-4">
        <div class="kp-card-header d-flex align-items-center justify-content-between">
            <h2 class="kp-card-title">Assigned Move-out Inspections</h2>
            <a href="{{ route('caretaker.inspections.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
        </div>

        @if($upcomingInspections->isEmpty())
            <p class="text-muted mb-0">No move-out inspections are currently assigned to you.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th>Scheduled</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingInspections as $inspection)
                            <tr>
                                <td class="fw-semibold">{{ $inspection->moveOutRequest->tenant->full_name ?? '—' }}</td>
                                <td>
                                    {{ $inspection->moveOutRequest->property->name ?? '—' }}
                                    @if($inspection->moveOutRequest->unit)
                                        / {{ $inspection->moveOutRequest->unit->unit_number }}
                                    @endif
                                </td>
                                <td>{{ optional($inspection->scheduled_at)->format('d M Y H:i') ?? '—' }}</td>
                                <td><span class="kp-badge {{ $inspection->statusBadgeClass() }}">{{ $inspection->statusLabel() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('caretaker.inspections.show', $inspection) }}" class="btn btn-sm btn-primary">
                                        Open
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="stat-card text-center py-4">
        <a href="{{ route('caretaker.maintenance.index') }}" class="btn btn-primary">
            <i class="bi bi-tools me-2"></i> View My Tasks
        </a>
    </div>
@endsection
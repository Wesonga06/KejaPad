@extends('layouts.app')

@section('page-title', 'Landlord Applications')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Landlord Applications</h1>
            <p class="kp-page-subtitle">Review and approve landlords before they can access landlord features.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-hourglass-split"></i></div></div>
                <div class="kp-kpi-value">{{ $counts['pending'] }}</div>
                <p class="kp-kpi-label">Pending</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-patch-check"></i></div></div>
                <div class="kp-kpi-value">{{ $counts['approved'] }}</div>
                <p class="kp-kpi-label">Approved</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon red"><i class="bi bi-x-octagon"></i></div></div>
                <div class="kp-kpi-value">{{ $counts['rejected'] }}</div>
                <p class="kp-kpi-label">Rejected</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-pause-circle"></i></div></div>
                <div class="kp-kpi-value">{{ $counts['suspended'] }}</div>
                <p class="kp-kpi-label">Suspended</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Applications</h2>
            <div class="d-flex gap-2 flex-wrap">
                @php
                    $tabs = [
                        '' => 'All',
                        'pending_approval' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'suspended' => 'Suspended',
                    ];
                @endphp
                @foreach($tabs as $value => $label)
                    <a href="{{ route('super_admin.landlord-applications.index', $value ? ['status' => $value] : []) }}"
                       class="btn btn-sm {{ (string) $status === (string) $value ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>

        @if($applications->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-person-badge"></i>
                <p class="mb-0">No landlord applications found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Reviewed</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($applications as $application)
                            <tr>
                                <td class="fw-semibold">{{ $application->name }}</td>
                                <td>{{ $application->email }}</td>
                                <td>
                                    @php
                                        $badge = match($application->status) {
                                            'approved' => 'kp-badge-success',
                                            'rejected' => 'kp-badge-danger',
                                            'suspended' => 'kp-badge-muted',
                                            default => 'kp-badge-warning',
                                        };
                                    @endphp
                                    <span class="kp-badge {{ $badge }}">{{ $application->statusLabel() }}</span>
                                </td>
                                <td>{{ $application->created_at?->format('d M Y') }}</td>
                                <td class="text-muted" style="font-size:0.85rem;">
                                    {{ $application->approved_at?->format('d M Y') ?? $application->rejected_at?->format('d M Y') ?? '—' }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('super_admin.landlord-applications.show', $application) }}"
                                       class="btn btn-sm btn-outline-primary">Review</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $applications->links() }}</div>
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('page-title', 'System Reports')

@php
    $human = fn ($value) => ucfirst(str_replace(['_', '-'], ' ', $value));
    $money = fn ($value) => 'KES '.number_format((float) $value, 0);
    $exportLabels = [
        'users' => 'Users',
        'applications' => 'Applications',
        'properties' => 'Properties',
        'rent' => 'Rent',
        'deposits' => 'Deposits',
        'move-outs' => 'Move-outs',
        'sms' => 'SMS',
        'audit' => 'Audit',
    ];
    $query = array_filter(['from' => $from?->toDateString(), 'to' => $to?->toDateString()]);
@endphp

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">System Reports</h1>
            <p class="kp-page-subtitle">
                Platform-wide activity{{ ($from || $to) ? ' for the selected period' : ' (all time)' }}.
            </p>
        </div>
    </div>

    <div class="kp-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-8">
                <form method="GET" action="{{ route('super_admin.reports.index') }}" class="row g-2 align-items-end">
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">From</label>
                        <input type="date" name="from" value="{{ $from?->toDateString() }}" class="form-control">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label fw-semibold">To</label>
                        <input type="date" name="to" value="{{ $to?->toDateString() }}" class="form-control">
                    </div>
                    <div class="col-12 col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Apply</button>
                        @if($from || $to)
                            <a href="{{ route('super_admin.reports.index') }}" class="btn btn-outline-secondary">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold">Export CSV</label>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($exportLabels as $key => $label)
                        <a href="{{ route('super_admin.reports.export', array_merge(['report' => $key], $query)) }}"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i> {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-people"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($summary['users']['total']) }}</div>
                <p class="kp-kpi-label">Users</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-buildings"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($summary['portfolio']['properties']) }}</div>
                <p class="kp-kpi-label">Properties</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-person-check"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($summary['portfolio']['tenants']) }}</div>
                <p class="kp-kpi-label">Active tenants</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-cash-coin"></i></div></div>
                <div class="kp-kpi-value">{{ $money($summary['rent']['collected']) }}</div>
                <p class="kp-kpi-label">Rent collected</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header"><h2 class="kp-card-title">Users by role</h2></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            @foreach($summary['users']['by_role'] as $role => $count)
                                <tr><td>{{ $human($role) }}</td><td class="text-end fw-semibold">{{ number_format($count) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    <span class="kp-badge kp-badge-success">Active: {{ number_format($summary['users']['active']) }}</span>
                    <span class="kp-badge kp-badge-muted">Inactive: {{ number_format($summary['users']['inactive']) }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header"><h2 class="kp-card-title">Landlord applications</h2></div>
                <div class="row g-3">
                    <div class="col-6 col-md-3"><div class="kp-kpi-value">{{ number_format($summary['applications']['pending']) }}</div><p class="kp-kpi-label">Pending</p></div>
                    <div class="col-6 col-md-3"><div class="kp-kpi-value">{{ number_format($summary['applications']['approved']) }}</div><p class="kp-kpi-label">Approved</p></div>
                    <div class="col-6 col-md-3"><div class="kp-kpi-value">{{ number_format($summary['applications']['rejected']) }}</div><p class="kp-kpi-label">Rejected</p></div>
                    <div class="col-6 col-md-3"><div class="kp-kpi-value">{{ number_format($summary['applications']['suspended']) }}</div><p class="kp-kpi-label">Suspended</p></div>
                </div>
                <hr>
                <p class="text-muted mb-0" style="font-size:0.85rem;">
                    Review actions in period: <strong>{{ number_format($summary['applications']['actions']) }}</strong>
                </p>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header"><h2 class="kp-card-title">Portfolio</h2></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr><td>Properties (active)</td><td class="text-end fw-semibold">{{ number_format($summary['portfolio']['properties_active']) }} / {{ number_format($summary['portfolio']['properties']) }}</td></tr>
                            <tr><td>Units</td><td class="text-end fw-semibold">{{ number_format($summary['portfolio']['units']) }}</td></tr>
                            <tr><td>Units occupied</td><td class="text-end fw-semibold">{{ number_format($summary['portfolio']['units_occupied']) }}</td></tr>
                            <tr><td>Units vacant</td><td class="text-end fw-semibold">{{ number_format($summary['portfolio']['units_vacant']) }}</td></tr>
                            <tr><td>Units reserved</td><td class="text-end fw-semibold">{{ number_format($summary['portfolio']['units_reserved']) }}</td></tr>
                            <tr><td>Caretakers (active)</td><td class="text-end fw-semibold">{{ number_format($summary['portfolio']['caretakers']) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header"><h2 class="kp-card-title">Rent performance</h2></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr><td>Records</td><td class="text-end fw-semibold">{{ number_format($summary['rent']['records']) }}</td></tr>
                            <tr><td>Due</td><td class="text-end fw-semibold">{{ $money($summary['rent']['due']) }}</td></tr>
                            <tr><td>Collected</td><td class="text-end fw-semibold">{{ $money($summary['rent']['collected']) }}</td></tr>
                            <tr><td>Outstanding</td><td class="text-end fw-semibold">{{ $money($summary['rent']['outstanding']) }}</td></tr>
                            <tr><td>Overdue</td><td class="text-end fw-semibold text-danger">{{ $money($summary['rent']['overdue_amount']) }}</td></tr>
                            <tr><td>Collection rate</td><td class="text-end fw-semibold">{{ $summary['rent']['collection_rate'] }}%</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex gap-2 flex-wrap">
                    @foreach($summary['rent']['by_status'] as $status => $count)
                        <span class="kp-badge kp-badge-muted">{{ $human($status) }}: {{ number_format($count) }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header"><h2 class="kp-card-title">Deposits</h2></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <tbody>
                            <tr><td>Deposits</td><td class="text-end fw-semibold">{{ number_format($summary['deposits']['total']) }}</td></tr>
                            <tr><td>Held</td><td class="text-end fw-semibold">{{ $money($summary['deposits']['held']) }}</td></tr>
                            <tr><td>Required</td><td class="text-end fw-semibold">{{ $money($summary['deposits']['required']) }}</td></tr>
                            <tr><td>Refunded</td><td class="text-end fw-semibold">{{ $money($summary['deposits']['refunded']) }}</td></tr>
                            <tr><td>Deductions approved</td><td class="text-end fw-semibold">{{ $money($summary['deposits']['deducted']) }}</td></tr>
                            <tr><td>Verified deposits</td><td class="text-end fw-semibold">{{ number_format($summary['deposits']['verified']) }} / {{ number_format($summary['deposits']['total']) }}</td></tr>
                            <tr><td>Fully refunded</td><td class="text-end fw-semibold">{{ number_format($summary['deposits']['refunded_count']) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header"><h2 class="kp-card-title">Move-outs &amp; inspections</h2></div>
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-4"><div class="kp-kpi-value">{{ number_format($summary['move_outs']['total']) }}</div><p class="kp-kpi-label">Requests</p></div>
                    <div class="col-6 col-md-4"><div class="kp-kpi-value">{{ number_format($summary['move_outs']['cleared']) }}</div><p class="kp-kpi-label">Cleared</p></div>
                    <div class="col-6 col-md-4"><div class="kp-kpi-value">{{ number_format($summary['move_outs']['pending_clearance']) }}</div><p class="kp-kpi-label">Awaiting clearance</p></div>
                </div>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    @foreach($summary['move_outs']['by_status'] as $status => $count)
                        <span class="kp-badge kp-badge-muted">{{ $human($status) }}: {{ number_format($count) }}</span>
                    @endforeach
                </div>
                <hr>
                <p class="text-muted mb-2" style="font-size:0.85rem;">
                    Inspections: <strong>{{ number_format($summary['inspections']['total']) }}</strong>
                    (completed {{ number_format($summary['inspections']['completed']) }})
                </p>
                <div class="d-flex gap-2 flex-wrap">
                    @foreach($summary['inspections']['by_status'] as $status => $count)
                        <span class="kp-badge kp-badge-muted">{{ $human($status) }}: {{ number_format($count) }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header"><h2 class="kp-card-title">Notifications &amp; SMS</h2></div>
                <div class="row g-3 mb-3">
                    <div class="col-6"><div class="kp-kpi-value">{{ number_format($summary['notifications']['total']) }}</div><p class="kp-kpi-label">Notifications</p></div>
                    <div class="col-6"><div class="kp-kpi-value">{{ number_format($summary['notifications']['unread']) }}</div><p class="kp-kpi-label">Unread</p></div>
                </div>
                <div class="d-flex gap-2 flex-wrap mb-3">
                    @foreach($summary['notifications']['by_type'] as $type => $count)
                        <span class="kp-badge kp-badge-muted">{{ \Illuminate\Support\Str::headline(class_basename($type)) }}: {{ number_format($count) }}</span>
                    @endforeach
                </div>
                <hr>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="kp-badge kp-badge-muted">SMS total: {{ number_format($summary['sms']['total']) }}</span>
                    <span class="kp-badge kp-badge-success">Sent: {{ number_format($summary['sms']['sent']) }}</span>
                    <span class="kp-badge kp-badge-danger">Failed: {{ number_format($summary['sms']['failed']) }}</span>
                    <span class="kp-badge kp-badge-warning">Queued: {{ number_format($summary['sms']['queued']) }}</span>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="kp-card">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Recent administrative activity</h2>
                    <p class="kp-card-subtitle">
                        {{ number_format($summary['audit']['total']) }} entries in period.
                    </p>
                </div>
                @if($summary['audit']['recent']->isEmpty())
                    <div class="kp-empty">
                        <i class="bi bi-clock-history"></i>
                        <p class="mb-0">No administrative activity recorded in this period.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr><th>Action</th><th>Description</th><th>By</th><th>When</th></tr>
                            </thead>
                            <tbody>
                                @foreach($summary['audit']['recent'] as $log)
                                    <tr>
                                        <td><span class="kp-badge {{ $log->badgeClass() }}">{{ $log->actionLabel() }}</span></td>
                                        <td>{{ $log->description }}</td>
                                        <td>{{ $log->user?->name ?? 'System' }}</td>
                                        <td class="text-muted" style="font-size:0.85rem;">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

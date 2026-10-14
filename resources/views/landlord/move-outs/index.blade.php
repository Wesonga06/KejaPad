@extends('layouts.app')

@section('page-title', 'Move-outs')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Move-outs</h1>
            <p class="kp-page-subtitle">Manage tenant notices to vacate, inspections, clearance and deposit settlement.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-box-arrow-right"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['open'] }}</div>
                <p class="kp-kpi-label">Open Requests</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-hourglass-split"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['awaiting_review'] }}</div>
                <p class="kp-kpi-label">Awaiting Review</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-clipboard-check"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['inspection'] }}</div>
                <p class="kp-kpi-label">In Inspection / Clearance</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check2-circle"></i></div></div>
                <div class="kp-kpi-value">{{ $stats['completed'] }}</div>
                <p class="kp-kpi-label">Completed</p>
            </div>
        </div>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Notice to Vacate requests</h2>
            <form method="GET" action="{{ route('landlord.move-outs.index') }}" class="d-flex gap-2 flex-wrap">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                       placeholder="Search tenant..." style="max-width:200px;">
                <select name="property_id" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                    <option value="">All properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}" @selected(request('property_id') == $property->id)>{{ $property->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach([
                        'submitted' => 'Submitted',
                        'under_review' => 'Under Review',
                        'awaiting_tenant_info' => 'Awaiting Tenant Info',
                        'approved' => 'Approved',
                        'inspection_scheduled' => 'Inspection Scheduled',
                        'inspection_completed' => 'Inspection Completed',
                        'awaiting_clearance' => 'Awaiting Clearance',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>

        @if($moveOuts->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-box-arrow-right"></i>
                <p class="mb-0">No move-out requests found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th>Proposed Move-out</th>
                            <th>Earliest Permitted</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($moveOuts as $moveOut)
                            <tr>
                                <td class="fw-semibold">{{ $moveOut->tenant->full_name ?? '—' }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $moveOut->property->name ?? '—' }}
                                    <span class="text-muted">/ {{ $moveOut->unit->unit_number ?? '—' }}</span>
                                </td>
                                <td>{{ optional($moveOut->proposed_move_out_date)->format('d M Y') }}</td>
                                <td class="text-muted">{{ optional($moveOut->earliest_permitted_date)->format('d M Y') }}</td>
                                <td><span class="kp-badge {{ $moveOut->statusBadgeClass() }}">{{ $moveOut->statusLabelText() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('landlord.move-outs.show', $moveOut) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $moveOuts->links() }}</div>
        @endif
    </div>
@endsection

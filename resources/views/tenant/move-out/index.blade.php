@extends('layouts.app')

@section('page-title', 'Notice to Vacate')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Notice to Vacate</h1>
            <p class="kp-page-subtitle">
                {{ $tenant->property->name ?? '—' }} / {{ $tenant->unit->unit_number ?? '—' }}
            </p>
        </div>
        <div class="kp-page-actions">
            @if(! $active)
                <a href="{{ route('tenant.move-out.create') }}" class="btn btn-primary">
                    <i class="bi bi-box-arrow-right me-2"></i> Submit Notice to Vacate
                </a>
            @endif
        </div>
    </div>

    <div class="kp-card mb-4" style="border-left:4px solid var(--kp-gold);">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-info-circle" style="font-size:1.3rem; color:var(--kp-gold);"></i>
            <div>
                <div class="fw-semibold">Notice period: {{ rtrim(rtrim(number_format($months, 2), '0'), '.') }} month(s)</div>
                <div class="text-muted" style="font-size:0.87rem;">
                    Your move-out date must be on or after {{ $earliest->format('d M Y') }} (calendar months) to satisfy the required notice period.
                </div>
            </div>
        </div>
    </div>

    @if($active)
        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Open request</h2>
                <span class="kp-badge {{ $active->statusBadgeClass() }}">{{ $active->statusLabelText() }}</span>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Proposed move-out date</div>
                    <div class="fw-semibold">{{ optional($active->proposed_move_out_date)->format('d M Y') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Earliest permitted</div>
                    <div class="fw-semibold">{{ optional($active->earliest_permitted_date)->format('d M Y') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Submitted</div>
                    <div class="fw-semibold">{{ optional($active->submitted_at)->format('d M Y') }}</div>
                </div>
            </div>
            @if($active->clarification_message)
                <div class="alert alert-warning mt-3 mb-0" style="font-size:0.87rem;">
                    <strong>Information requested:</strong> {{ $active->clarification_message }}
                </div>
            @endif
            <div class="d-flex gap-2 mt-3">
                <a href="{{ route('tenant.move-out.show', $active) }}" class="btn btn-outline-primary">View Details</a>
                <form method="POST" action="{{ route('tenant.move-out.cancel', $active) }}"
                      onsubmit="return confirm('Cancel your Notice to Vacate?');">
                    @csrf @method('PATCH')
                    <button class="btn btn-outline-danger">Cancel Request</button>
                </form>
            </div>
        </div>
    @endif

    <div class="kp-card">
        <div class="kp-card-header"><h2 class="kp-card-title">Request history</h2></div>
        @if($requests->isEmpty())
            <div class="kp-empty"><i class="bi bi-clipboard"></i><p class="mb-0">You have not submitted a Notice to Vacate yet.</p></div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Submitted</th><th>Proposed Move-out</th><th>Status</th><th class="text-end"></th></tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $request)
                            <tr>
                                <td class="text-muted">{{ optional($request->submitted_at)->format('d M Y') }}</td>
                                <td>{{ optional($request->proposed_move_out_date)->format('d M Y') }}</td>
                                <td><span class="kp-badge {{ $request->statusBadgeClass() }}">{{ $request->statusLabelText() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('tenant.move-out.show', $request) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@extends('layouts.app')

@section('page-title', 'Notice to Vacate Details')

@section('content')
    @php
        $inspection = $moveOut->inspection;
    @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Notice to Vacate</h1>
            <p class="kp-page-subtitle">
                {{ $moveOut->property->name ?? '—' }} / {{ $moveOut->unit->unit_number ?? '—' }}
                &middot; <span class="kp-badge {{ $moveOut->statusBadgeClass() }}">{{ $moveOut->statusLabelText() }}</span>
            </p>
        </div>
        <div class="kp-page-actions">
            @if($moveOut->settlement && $moveOut->settlement->isVisibleToTenant())
                <a href="{{ route('tenant.settlement.show', $moveOut->settlement) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-earmark-text me-2"></i> Deposit Settlement
                </a>
            @endif
            <a href="{{ route('tenant.move-out.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
            @if($moveOut->status === 'submitted' || $moveOut->status === 'under_review' || $moveOut->status === 'awaiting_tenant_info')
                <form method="POST" action="{{ route('tenant.move-out.cancel', $moveOut) }}"
                      onsubmit="return confirm('Cancel this Notice to Vacate?');">
                    @csrf @method('PATCH')
                    <button class="btn btn-outline-danger">Cancel Request</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Request summary</h2></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Proposed move-out date</div>
                        <div class="fw-semibold">{{ optional($moveOut->proposed_move_out_date)->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Earliest permitted</div>
                        <div class="fw-semibold">{{ optional($moveOut->earliest_permitted_date)->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Actual move-out date</div>
                        <div class="fw-semibold">{{ optional($moveOut->actual_move_out_date)->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Submitted</div>
                        <div class="fw-semibold">{{ optional($moveOut->submitted_at)->format('d M Y') }}</div>
                    </div>
                </div>

                @if($moveOut->clarification_message)
                    <div class="alert alert-warning mt-3 mb-0" style="font-size:0.87rem;">
                        <strong>Information requested by your landlord:</strong> {{ $moveOut->clarification_message }}
                    </div>
                @endif
                @if($moveOut->decision_reason)
                    <div class="alert alert-secondary mt-3 mb-0" style="font-size:0.87rem;">
                        <strong>Decision:</strong> {{ $moveOut->decision_reason }}
                    </div>
                @endif
            </div>

            <div class="kp-card">
                <div class="kp-card-header"><h2 class="kp-card-title">Move-out inspection</h2></div>
                @if(! $inspection)
                    <p class="text-muted mb-0">An inspection has not been scheduled yet.</p>
                @else
                    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <div class="text-muted small">Status</div>
                            <span class="kp-badge {{ $inspection->statusBadgeClass() }}">{{ $inspection->statusLabel() }}</span>
                        </div>
                        <div>
                            <div class="text-muted small">Scheduled</div>
                            <div class="fw-semibold">{{ optional($inspection->scheduled_at)->format('d M Y H:i') ?? '—' }}</div>
                        </div>
                        @if($inspection->isSubmitted())
                            <div>
                                <div class="text-muted small">Recommendation</div>
                                <div class="fw-semibold">{{ $inspection->recommendationLabel() }}</div>
                            </div>
                        @endif
                    </div>
                    @if($inspection->isSubmitted())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Checklist item</th><th>Condition</th></tr></thead>
                                <tbody>
                                    @foreach($inspection->items as $item)
                                        <tr>
                                            <td>{{ $item->label }}</td>
                                            <td>{{ $item->conditionLabel() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Security deposit</h2></div>
                @if(! $deposit)
                    <p class="text-muted mb-0">No deposit is held for your tenancy.</p>
                @else
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Status</span><span class="kp-badge {{ $deposit->statusBadgeClass() }}">{{ $deposit->statusLabel() }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Required</span><span class="fw-semibold">KES {{ number_format((float) $deposit->required_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Received</span><span class="fw-semibold">KES {{ number_format((float) $deposit->received_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-3"><span class="text-muted">Net refundable</span><span class="fw-semibold">KES {{ number_format($deposit->netRefundable(), 2) }}</span></div>
                    <a href="{{ route('tenant.deposit.show') }}" class="btn btn-outline-primary w-100">View My Deposit</a>
                @endif
            </div>

            <div class="kp-card">
                <div class="kp-card-header"><h2 class="kp-card-title">Progress</h2></div>
                <ol class="mb-0 ps-3" style="font-size:0.88rem;">
                    @php
                        $steps = [
                            'submitted' => 'Request submitted',
                            'under_review' => 'Under review',
                            'approved' => 'Approved',
                            'inspection_scheduled' => 'Inspection scheduled',
                            'inspection_completed' => 'Inspection completed',
                            'awaiting_clearance' => 'Awaiting clearance',
                            'completed' => 'Tenancy closed',
                        ];
                        $order = array_keys($steps);
                        $currentIndex = array_search($moveOut->status, $order, true);
                    @endphp
                    @foreach($steps as $key => $label)
                        @php $done = $currentIndex !== false && array_search($key, $order, true) <= $currentIndex; @endphp
                        <li class="mb-1" style="{{ $done ? 'font-weight:600;' : 'color:var(--kp-muted);' }}">
                            <i class="bi {{ $done ? 'bi-check-circle-fill text-success' : 'bi-circle' }} me-1"></i> {{ $label }}
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
@endsection

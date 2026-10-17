@extends('layouts.app')

@section('page-title', 'Deposit Settlement')

@section('content')
    @php
        $moveOut = $settlement->moveOutRequest;
        $proposedDeductions = $deposit
            ? $deposit->transactions->where('type', \App\Models\DepositTransaction::TYPE_DEDUCTION)
                ->where('status', \App\Models\DepositTransaction::STATUS_PROPOSED)
            : collect();
        $canRespond = ! $settlement->isSettled() && ! $settlement->isApproved();
    @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Deposit Settlement</h1>
            <p class="kp-page-subtitle">
                {{ $moveOut?->property->name ?? '—' }} / {{ $moveOut?->unit->unit_number ?? '—' }}
                &middot; <span class="kp-badge {{ $settlement->statusBadgeClass() }}">{{ $settlement->statusLabel() }}</span>
            </p>
        </div>
    </div>

    @if($settlement->isDisputed())
        <div class="alert alert-danger" style="font-size:0.88rem;">
            <i class="bi bi-exclamation-circle me-1"></i> You have disputed one or more proposed deductions. Your landlord will review your response.
        </div>
    @elseif($settlement->isSettled())
        <div class="alert alert-success" style="font-size:0.88rem;">
            <i class="bi bi-check-circle me-1"></i> This settlement is complete. Refunded: KES {{ number_format((float) $settlement->refunded_amount, 2) }}.
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-4">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-safe"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) $settlement->held_amount, 2) }}</div>
                <p class="kp-kpi-label">Deposit held</p>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-dash-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) $settlement->proposed_deductions, 2) }}</div>
                <p class="kp-kpi-label">Proposed deductions</p>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-arrow-return-left"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) ($settlement->approved_refund ?? $settlement->projectedRefund()), 2) }}</div>
                <p class="kp-kpi-label">{{ $settlement->approved_refund !== null ? 'Approved refund' : 'Projected refund' }}</p>
            </div>
        </div>
    </div>

    @if($comparison && ! $comparison['has_baseline'])
        <div class="alert alert-warning" style="font-size:0.86rem;">
            <i class="bi bi-exclamation-triangle me-1"></i>
            No move-in inspection baseline exists for this tenancy, so condition differences cannot be verified.
        </div>
    @endif

    <div class="kp-card mb-4">
        <div class="kp-card-header"><h2 class="kp-card-title">Move-in vs move-out evidence</h2></div>
        @if(! $comparison || empty($comparison['rows']))
            <p class="text-muted mb-0" style="font-size:0.86rem;">No inspection items are available to compare.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>Item</th><th>Move-in</th><th>Move-out</th><th>Classification</th></tr>
                    </thead>
                    <tbody>
                        @foreach($comparison['rows'] as $row)
                            <tr>
                                <td class="fw-semibold" style="font-size:0.85rem;">{{ $row['label'] }}</td>
                                <td style="font-size:0.85rem;">
                                    @if($row['missing_baseline'])
                                        <span class="text-muted">No baseline</span>
                                    @else
                                        {{ $row['move_in_label'] }}
                                    @endif
                                </td>
                                <td style="font-size:0.85rem;">{{ $row['move_out_label'] }}</td>
                                <td style="font-size:0.85rem;">
                                    @if($row['nature'])
                                        <span class="kp-badge {{ $row['nature'] === 'new_damage' ? 'kp-badge-danger' : ($row['nature'] === 'wear_tear' ? 'kp-badge-warning' : 'kp-badge-muted') }}">{{ $row['nature_label'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="kp-card mb-4">
        <div class="kp-card-header"><h2 class="kp-card-title">Proposed deductions</h2></div>
        @if(($deposit?->transactions->where('type', \App\Models\DepositTransaction::TYPE_DEDUCTION) ?? collect())->isEmpty())
            <p class="text-muted mb-0" style="font-size:0.86rem;">No deductions have been proposed.</p>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>Reason</th><th class="text-end">Amount</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @foreach($deposit->transactions->where('type', \App\Models\DepositTransaction::TYPE_DEDUCTION) as $transaction)
                            <tr>
                                <td style="font-size:0.85rem;">{{ $transaction->reason ?: '—' }}</td>
                                <td class="text-end">KES {{ number_format((float) $transaction->amount, 2) }}</td>
                                <td><span class="kp-badge {{ $transaction->statusBadgeClass() }}">{{ $transaction->statusLabel() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($settlement->tenant_comment)
        <div class="kp-card mb-4">
            <div class="kp-card-header"><h2 class="kp-card-title">Your last response</h2></div>
            <p class="mb-0" style="font-size:0.9rem;">{{ $settlement->tenant_comment }}</p>
            <div class="text-muted small mt-1">{{ optional($settlement->responded_at)->format('d M Y H:i') }}</div>
        </div>
    @endif

    @if($canRespond)
        <div class="kp-card">
            <div class="kp-card-header"><h2 class="kp-card-title">Your response</h2></div>
            <form method="POST" action="{{ route('tenant.settlement.respond', $settlement) }}">
                @csrf
                @if($proposedDeductions->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">Dispute any deduction you disagree with</label>
                        @foreach($proposedDeductions as $transaction)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="dispute[]" value="{{ $transaction->id }}" id="dispute_{{ $transaction->id }}">
                                <label class="form-check-label" for="dispute_{{ $transaction->id }}">
                                    KES {{ number_format((float) $transaction->amount, 2) }} — {{ $transaction->reason ?: 'Deduction' }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label" for="tenant_comment">Your comment</label>
                    <textarea class="form-control" id="tenant_comment" name="tenant_comment" rows="3" maxlength="2000">{{ old('tenant_comment') }}</textarea>
                    @error('tenant_comment')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="acknowledge" value="1" id="acknowledge">
                    <label class="form-check-label" for="acknowledge">
                        I acknowledge receipt of this statement (this does not mean I agree with it).
                    </label>
                </div>
                <button class="btn btn-primary">
                    <i class="bi bi-send me-2"></i> Submit response
                </button>
            </form>
        </div>
    @endif
@endsection

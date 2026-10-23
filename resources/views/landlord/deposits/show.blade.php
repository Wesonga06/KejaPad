@extends('layouts.app')

@section('page-title', 'Deposit Details')

@section('content')
    @php
        $tenant = $deposit->tenant;
    @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Deposit — {{ $tenant->full_name ?? '—' }}</h1>
            <p class="kp-page-subtitle">
                {{ $deposit->property->name ?? '—' }} / {{ $deposit->unit->unit_number ?? '—' }}
                &middot; <span class="kp-badge {{ $deposit->statusBadgeClass() }}">{{ $deposit->statusLabel() }}</span>
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.deposits.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Deposits
            </a>
            @if($moveOut)
                <a href="{{ route('landlord.move-outs.show', $moveOut) }}" class="btn btn-outline-primary">
                    <i class="bi bi-box-arrow-right me-2"></i> View Move-out
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-cash-stack"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format((float) $deposit->required_amount, 2) }}</div>
                <p class="kp-kpi-label">Required</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check2-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format((float) $deposit->received_amount, 2) }}</div>
                <p class="kp-kpi-label">Received</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-dash-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($deposit->approvedDeductions(), 2) }}</div>
                <p class="kp-kpi-label">Approved Deductions</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-arrow-return-left"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($deposit->availableForRefund(), 2) }}</div>
                <p class="kp-kpi-label">Available to Refund</p>
                <p class="text-muted small mb-0">After approved deductions and pending refunds.</p>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="kp-card mb-4">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Transaction history</h2>
                    <span class="text-muted small">Deposits are held separately and are never treated as rent income.</span>
                </div>
                @if($deposit->transactions->isEmpty())
                    <div class="kp-empty"><i class="bi bi-journal-text"></i><p class="mb-0">No transactions recorded yet.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deposit->transactions->sortByDesc('id') as $tx)
                                    <tr>
                                        <td class="text-muted small">{{ $tx->created_at->format('d M Y') }}</td>
                                        <td><span class="kp-badge {{ $tx->typeBadgeClass() }}">{{ $tx->typeLabel() }}</span></td>
                                        <td class="text-end fw-semibold">KES {{ number_format((float) $tx->amount, 2) }}</td>
                                        <td><span class="kp-badge {{ $tx->statusBadgeClass() }}">{{ $tx->statusLabel() }}</span></td>
                                        <td style="font-size:0.85rem;">
                                            @if($tx->method)<div>Method: {{ ucfirst(str_replace('_', ' ', $tx->method)) }}</div>@endif
                                            @if($tx->reference)<div>Ref: {{ $tx->reference }}</div>@endif
                                            @if($tx->reason)<div>{{ $tx->reason }}</div>@endif
                                            @if($tx->recordedBy)<div class="text-muted">By {{ $tx->recordedBy->name }}</div>@endif
                                        </td>
                                        <td class="text-end">
                                            @if($tx->type === 'receipt' && $tx->status === 'recorded')
                                                <form method="POST" action="{{ route('landlord.deposits.receipts.verify', [$deposit, $tx]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-success">Verify</button>
                                                </form>
                                            @elseif($tx->type === 'deduction' && $tx->status === 'proposed')
                                                <form method="POST" action="{{ route('landlord.deposits.deductions.approve', [$deposit, $tx]) }}">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-primary">Approve</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-5">
            @if(! $deposit->isVerified())
                <div class="kp-card mb-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Verify deposit</h2></div>
                    @if($deposit->isSatisfied())
                        <p class="text-muted" style="font-size:0.85rem;">The required amount has been received. Verify to allow move-in.</p>
                        <form method="POST" action="{{ route('landlord.deposits.verify', $deposit) }}">
                            @csrf
                            <button class="btn btn-success w-100">Verify Deposit</button>
                        </form>
                    @else
                        <p class="text-muted mb-0" style="font-size:0.85rem;">
                            The full deposit has not been received yet. Outstanding: KES {{ number_format($deposit->outstanding(), 2) }}.
                        </p>
                    @endif
                </div>
            @endif

            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Authorized exception</h2></div>
                @if($deposit->hasException())
                    <div class="alert alert-warning mb-2" style="font-size:0.85rem;">
                        <i class="bi bi-shield-exclamation me-1"></i> Move-in is permitted without the full deposit under an authorized exception.
                    </div>
                    <p class="mb-1" style="font-size:0.88rem;">
                        <span class="text-muted small">Reason:</span> {{ $deposit->exception_reason }}
                    </p>
                    <p class="mb-0" style="font-size:0.85rem;">
                        <span class="text-muted small">Authorized by:</span> {{ $deposit->exceptionApprovedBy->name ?? '—' }}
                        on {{ optional($deposit->exception_approved_at)->format('d M Y H:i') }}
                    </p>
                @else
                    <p class="text-muted" style="font-size:0.85rem;">
                        If the deposit cannot be collected, record an authorized exception with a reason to allow move-in.
                    </p>
                    <form method="POST" action="{{ route('landlord.deposits.exception', $deposit) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="exception_reason">Reason <span class="text-danger">*</span></label>
                            <textarea name="exception_reason" id="exception_reason" rows="3" class="form-control" required>{{ old('exception_reason') }}</textarea>
                            @error('exception_reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-outline-warning w-100">Record Exception</button>
                    </form>
                @endif
            </div>

            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Record deposit received</h2></div>
                <form method="POST" action="{{ route('landlord.deposits.receipts.store', $deposit) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="amount">Amount (KES)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="amount" class="form-control" required>
                        @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label" for="method">Method</label>
                            <select name="method" id="method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="bank_transfer">Bank transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="reference">Reference</label>
                            <input type="text" name="reference" id="reference" class="form-control" maxlength="100">
                        </div>
                    </div>
                    <div class="form-check my-3">
                        <input type="checkbox" name="verify" id="verify" value="1" class="form-check-input">
                        <label class="form-check-label" for="verify">Verify this receipt now</label>
                    </div>
                    <button class="btn btn-primary w-100">Record Receipt</button>
                </form>
            </div>

            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Propose deduction</h2></div>
                <p class="text-muted" style="font-size:0.85rem;">Deductions are proposed first and only count once approved.</p>
                <form method="POST" action="{{ route('landlord.deposits.deductions.store', $deposit) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="deduction_amount">Amount (KES)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="deduction_amount" class="form-control" required>
                        @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reason">Reason</label>
                        <textarea name="reason" id="reason" rows="2" class="form-control" required></textarea>
                        @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="evidence">Evidence <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="evidence" id="evidence" rows="2" class="form-control"></textarea>
                    </div>
                    <button class="btn btn-outline-warning w-100">Propose Deduction</button>
                </form>
            </div>

            <div class="kp-card">
                <div class="kp-card-header"><h2 class="kp-card-title">Record refund</h2></div>
                @if($deposit->netRefundable() > 0)
                    <p class="text-muted" style="font-size:0.85rem;">Net refundable: KES {{ number_format($deposit->netRefundable(), 2) }}</p>
                    <form method="POST" action="{{ route('landlord.deposits.refunds.store', $deposit) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="refund_amount">Amount (KES)</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $deposit->netRefundable() }}"
                                   name="amount" id="refund_amount" class="form-control" required>
                            @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="refund_method">Method</label>
                                <select name="method" id="refund_method" class="form-select">
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="refund_reference">Reference</label>
                                <input type="text" name="reference" id="refund_reference" class="form-control" maxlength="100">
                            </div>
                        </div>
                        <button class="btn btn-success w-100 mt-3">Record Refund</button>
                    </form>
                @else
                    <p class="text-muted mb-0" style="font-size:0.85rem;">There is no refundable balance at this time.</p>
                @endif
            </div>
        </div>
    </div>
@endsection

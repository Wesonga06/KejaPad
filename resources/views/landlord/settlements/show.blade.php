@extends('layouts.app')

@section('page-title', 'Deposit Settlement')

@section('content')
    @php
        $moveOut = $settlement->moveOutRequest;
        $tenant = $moveOut?->tenant;
        $approvedDeductions = $deposit?->approvedDeductions() ?? 0;
        $proposedDeductions = $deposit
            ? $deposit->transactions->where('type', \App\Models\DepositTransaction::TYPE_DEDUCTION)
                ->where('status', \App\Models\DepositTransaction::STATUS_PROPOSED)
            : collect();
        $deductionLedger = $deposit
            ? $deposit->transactions->where('type', \App\Models\DepositTransaction::TYPE_DEDUCTION)
            : collect();
    @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Deposit Settlement — {{ $tenant->full_name ?? '—' }}</h1>
            <p class="kp-page-subtitle">
                {{ $moveOut?->property->name ?? '—' }} / {{ $moveOut?->unit->unit_number ?? '—' }}
                &middot; <span class="kp-badge {{ $settlement->statusBadgeClass() }}">{{ $settlement->statusLabel() }}</span>
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.settlements.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-safe"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) $settlement->held_amount, 2) }}</div>
                <p class="kp-kpi-label">Deposit held</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-dash-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) $settlement->proposed_deductions, 2) }}</div>
                <p class="kp-kpi-label">Proposed deductions</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-arrow-return-left"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) ($settlement->approved_refund ?? $settlement->proposed_refund), 2) }}</div>
                <p class="kp-kpi-label">{{ $settlement->approved_refund !== null ? 'Approved refund' : 'Proposed refund' }}</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check2-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.1rem;">KES {{ number_format((float) $settlement->refunded_amount, 2) }}</div>
                <p class="kp-kpi-label">Refunded</p>
            </div>
        </div>
    </div>

    @if($comparison && ! $comparison['has_baseline'])
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>No move-in baseline evidence.</strong> {{ $comparison['flag'] }}
            Differences cannot be verified against move-in condition.
        </div>
    @endif

    @if($comparison && ! empty($comparison['missing_evidence']))
        <div class="alert alert-light border" style="font-size:0.86rem;">
            <strong>Evidence notes:</strong>
            <ul class="mb-0 mt-1">
                @foreach($comparison['missing_evidence'] as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="kp-card mb-4">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Move-in vs move-out evidence</h2>
            @if($comparison)
                <span class="kp-badge kp-badge-muted">{{ count($comparison['rows']) }} item(s) compared</span>
            @endif
        </div>

        @if(! $comparison || empty($comparison['rows']))
            <div class="kp-empty">
                <i class="bi bi-clipboard-x"></i>
                <p class="mb-0">No inspection items to compare yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:24%;">Item</th>
                            <th style="width:22%;">Move-in</th>
                            <th style="width:22%;">Move-out</th>
                            <th style="width:20%;">Classification</th>
                            <th>Change</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparison['rows'] as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['label'] }}</td>
                                <td style="font-size:0.85rem;">
                                    @if($row['missing_baseline'])
                                        <span class="text-muted">No baseline</span>
                                    @else
                                        {{ $row['move_in_label'] }}
                                        @if($row['move_in_comment'])
                                            <div class="text-muted">{{ $row['move_in_comment'] }}</div>
                                        @endif
                                    @endif
                                </td>
                                <td style="font-size:0.85rem;">
                                    {{ $row['move_out_label'] }}
                                    @if($row['move_out_comment'])
                                        <div class="text-muted">{{ $row['move_out_comment'] }}</div>
                                    @endif
                                </td>
                                <td style="font-size:0.85rem;">
                                    @if($row['nature'])
                                        <span class="kp-badge {{ $row['nature'] === 'new_damage' ? 'kp-badge-danger' : ($row['nature'] === 'wear_tear' ? 'kp-badge-warning' : 'kp-badge-muted') }}">{{ $row['nature_label'] }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row['changed'])
                                        <span class="kp-badge kp-badge-warning">Changed</span>
                                    @else
                                        <span class="text-muted small">same</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Proposed deductions</h2></div>

                @if($deductionLedger->isEmpty())
                    <div class="kp-empty">
                        <i class="bi bi-dash-circle"></i>
                        <p class="mb-0">No deductions have been proposed for this deposit.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Reason</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                    <th>Tenant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deductionLedger as $transaction)
                                    <tr>
                                        <td style="font-size:0.85rem;">{{ $transaction->reason ?: '—' }}</td>
                                        <td class="text-end">KES {{ number_format((float) $transaction->amount, 2) }}</td>
                                        <td><span class="kp-badge {{ $transaction->statusBadgeClass() }}">{{ $transaction->statusLabel() }}</span></td>
                                        <td style="font-size:0.82rem;">
                                            @if($transaction->isDisputed())
                                                <span class="kp-badge kp-badge-danger">Disputed</span>
                                                @if($transaction->tenant_comment)
                                                    <div class="text-muted">{{ $transaction->tenant_comment }}</div>
                                                @endif
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

            @if($settlement->isDraft() || $settlement->isAwaitingTenant())
                <div class="kp-card mb-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Send to tenant</h2></div>
                    <p class="text-muted" style="font-size:0.86rem;">
                        The tenant will see the deposit held, every proposed deduction with its reason, and the projected refund.
                        Nothing is final until they have had the chance to respond.
                    </p>
                    <form method="POST" action="{{ route('landlord.settlements.send', $settlement) }}">
                        @csrf
                        <button class="btn btn-primary">
                            <i class="bi bi-send me-2"></i> {{ $settlement->isAwaitingTenant() ? 'Re-send statement' : 'Send statement to tenant' }}
                        </button>
                    </form>
                </div>
            @endif

            @if($settlement->tenant_comment)
                <div class="kp-card mb-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Tenant response</h2></div>
                    <p class="mb-1" style="font-size:0.9rem;">{{ $settlement->tenant_comment }}</p>
                    <div class="text-muted small">
                        Responded {{ optional($settlement->responded_at)->format('d M Y H:i') }}
                        @if($settlement->isAcknowledged())
                            &middot; acknowledged {{ optional($settlement->acknowledged_at)->format('d M Y H:i') }}
                        @endif
                    </div>
                </div>
            @endif

            @if(! $settlement->isSettled())
                <div class="kp-card">
                    <div class="kp-card-header"><h2 class="kp-card-title">Landlord decision</h2></div>
                    @if($deposit === null)
                        <div class="alert alert-warning mb-0">No security deposit is held for this tenancy, so no deductions or refund can be decided.</div>
                    @else
                        <form method="POST" action="{{ route('landlord.settlements.decision', $settlement) }}">
                            @csrf
                            <p class="text-muted" style="font-size:0.86rem;">
                                Review each proposed deduction. Approved deductions are applied to the deposit;
                                rejected ones are reversed. The refund is the balance of what was actually received.
                            </p>

                            @error('approved_refund')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                            @if($proposedDeductions->isNotEmpty())
                                <div class="table-responsive mb-3">
                                    <table class="table align-middle">
                                        <thead>
                                            <tr><th>Deduction</th><th class="text-end" style="width:140px;">Amount</th><th style="width:190px;">Decision</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach($proposedDeductions as $transaction)
                                                <tr>
                                                    <td style="font-size:0.85rem;">
                                                        {{ $transaction->reason ?: '—' }}
                                                        @if($transaction->requires_authorisation)
                                                            <span class="kp-badge kp-badge-warning">Needs authorisation</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end"
                                                               name="amounts[{{ $transaction->id }}]" value="{{ number_format((float) $transaction->amount, 2, '.', '') }}">
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="decisions[{{ $transaction->id }}]" value="approve">
                                                            <label class="form-check-label small">Approve</label>
                                                        </div>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="decisions[{{ $transaction->id }}]" value="reject">
                                                            <label class="form-check-label small">Reject</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <input type="hidden" name="__decisions_present" value="1">
                            @endif

                            <div class="mb-3">
                                <label class="form-label" for="approved_refund">Approved refund (KES)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="approved_refund" name="approved_refund"
                                       value="{{ old('approved_refund', number_format((float) $settlement->projectedRefund(), 2, '.', '')) }}">
                                <div class="form-text">Projected refund from current proposals: KES {{ number_format($settlement->projectedRefund(), 2) }}. Leave as-is to refund the full verified balance.</div>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="confirm_authorisation" value="1" id="confirm_authorisation">
                                <label class="form-check-label" for="confirm_authorisation">
                                    I explicitly authorise any deduction flagged as needing authorisation (e.g. arrears). No deposit is applied to rent automatically.
                                </label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="landlord_findings">Findings / documented reasons</label>
                                <textarea class="form-control" id="landlord_findings" name="landlord_findings" rows="3" maxlength="2000">{{ old('landlord_findings', $settlement->landlord_findings) }}</textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="decision_reason">Refund note (optional)</label>
                                <input type="text" class="form-control" id="decision_reason" name="decision_reason" maxlength="1000" value="{{ old('decision_reason', $settlement->decision_reason) }}">
                            </div>

                            <button class="btn btn-primary">
                                <i class="bi bi-check2-square me-2"></i> Record decision
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-5">
            @if($settlement->isApproved())
                <div class="kp-card mb-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Settle &amp; refund</h2></div>
                    <p class="text-muted" style="font-size:0.86rem;">
                        Confirming settlement records the refund payout through the verified refund flow.
                        It is only marked refunded once the payout is recorded.
                    </p>
                    <form method="POST" action="{{ route('landlord.settlements.settle', $settlement) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="method">Refund method</label>
                            <select class="form-select" id="method" name="method">
                                <option value="">Select...</option>
                                @foreach(['mpesa' => 'M-Pesa', 'cash' => 'Cash', 'bank_transfer' => 'Bank transfer', 'cheque' => 'Cheque', 'other' => 'Other'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reference">Payment reference</label>
                            <input type="text" class="form-control" id="reference" name="reference" maxlength="100" value="{{ old('reference') }}">
                            <div class="form-text">The same reference cannot be recorded twice.</div>
                        </div>
                        @error('reference')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                        <button class="btn btn-success">
                            <i class="bi bi-check2-circle me-2"></i> Complete settlement
                        </button>
                    </form>
                </div>
            @endif

            @if($settlement->isSettled())
                <div class="kp-card mb-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Settlement summary</h2></div>
                    <dl class="row mb-0" style="font-size:0.9rem;">
                        <dt class="col-6">Deposit held</dt><dd class="col-6 text-end">KES {{ number_format((float) $settlement->held_amount, 2) }}</dd>
                        <dt class="col-6">Approved deductions</dt><dd class="text-end">KES {{ number_format((float) $approvedDeductions, 2) }}</dd>
                        <dt class="col-6">Refunded</dt><dd class="text-end">KES {{ number_format((float) $settlement->refunded_amount, 2) }}</dd>
                        <dt class="col-6">Retained</dt><dd class="text-end">KES {{ number_format((float) $settlement->retained_amount, 2) }}</dd>
                    </div>
                </div>
            @endif

            <div class="kp-card">
                <div class="kp-card-header"><h2 class="kp-card-title">History</h2></div>
                @if($settlement->events->isEmpty())
                    <p class="text-muted mb-0" style="font-size:0.85rem;">No history yet.</p>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach($settlement->events as $event)
                            <li class="d-flex gap-3 mb-3">
                                <div class="text-muted" style="font-size:0.75rem;min-width:110px;">
                                    {{ optional($event->created_at)->format('d M Y H:i') }}
                                </div>
                                <div>
                                    <div style="font-size:0.86rem;" class="fw-semibold">{{ $event->label() }}</div>
                                    @if($event->message)
                                        <div class="text-muted" style="font-size:0.82rem;">{{ $event->message }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size:0.74rem;">
                                        {{ $event->user->name ?? 'System' }}{{ $event->role ? ' ('.str_replace('_', ' ', $event->role).')' : '' }}
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection

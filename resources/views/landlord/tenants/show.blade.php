@extends('layouts.app')

@section('page-title', 'Tenant Account')

@section('content')
    @php
        $statusMeta = $summary['status'];
        $trendMeta = [
            'increasing' => ['label' => 'Increasing', 'badge' => 'danger', 'icon' => 'arrow-up-right'],
            'decreasing' => ['label' => 'Decreasing', 'badge' => 'success', 'icon' => 'arrow-down-right'],
            'unchanged'  => ['label' => 'Unchanged', 'badge' => 'secondary', 'icon' => 'arrow-right'],
        ][$indicators['trend']] ?? ['label' => 'Unchanged', 'badge' => 'secondary', 'icon' => 'arrow-right'];
    @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">{{ $tenant->full_name }}</h1>
            <p class="kp-page-subtitle">
                {{ $tenant->property->name }} — Unit {{ $tenant->unit->unit_number }}
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.tenants.edit', $tenant) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i> Edit
            </a>
            <a href="{{ route('landlord.tenants.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    {{-- Rent account summary --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-xl-8">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0">Rent Account</h6>
                    <span class="badge rounded-pill px-3 py-2"
                          style="background:{{ $statusMeta['color'] }}; font-size:0.8rem;">
                        <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i>
                        {{ $statusMeta['label'] }}
                        @if($summary['max_overdue_days'] > 0)
                            — {{ $summary['max_overdue_days'] }} days overdue
                        @endif
                    </span>
                </div>

                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Total Charged</small>
                        <span class="fw-bold" style="font-size:1.05rem;">KES {{ number_format($summary['charged'], 2) }}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Total Paid</small>
                        <span class="fw-bold text-success" style="font-size:1.05rem;">KES {{ number_format($summary['paid'], 2) }}</span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Outstanding</small>
                        <span class="fw-bold {{ $summary['outstanding'] > 0 ? 'text-danger' : 'text-success' }}" style="font-size:1.05rem;">
                            KES {{ number_format($summary['outstanding'], 2) }}
                        </span>
                    </div>
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Arrears</small>
                        <span class="fw-bold {{ $summary['arrears'] > 0 ? 'text-danger' : 'text-success' }}" style="font-size:1.05rem;">
                            KES {{ number_format($summary['arrears'], 2) }}
                        </span>
                    </div>
                    @if(($summary['credit'] ?? 0) > 0)
                    <div class="col-6 col-md-3">
                        <small class="text-muted d-block">Credit held</small>
                        <span class="fw-bold text-success" style="font-size:1.05rem;">
                            KES {{ number_format($summary['credit'], 2) }}
                        </span>
                    </div>
                    @endif
                </div>

                @if($summary['oldest_overdue'])
                    <div class="alert alert-warning mt-3 mb-0 py-2" style="font-size:0.82rem;">
                        <i class="bi bi-calendar-x me-1"></i>
                        Oldest overdue period since
                        <strong>{{ $summary['oldest_overdue']->format('d M Y') }}</strong>
                        ({{ $summary['periods_overdue'] }} period{{ $summary['periods_overdue'] === 1 ? '' : 's' }} in arrears).
                    </div>
                @endif
            </div>
        </div>

        {{-- Payment behaviour --}}
        <div class="col-12 col-xl-4">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3">Payment Behaviour <span class="text-muted fw-normal">(last 6 months)</span></h6>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Paid this month</span>
                    <span class="fw-semibold">KES {{ number_format($indicators['paid_this_month'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Payments (period)</span>
                    <span class="fw-semibold">{{ $indicators['payments_count'] }} ({{ $indicators['payment_frequency'] }}/mo)</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Last payment</span>
                    <span class="fw-semibold">{{ $indicators['last_payment_date']?->format('d M Y') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Balance trend</span>
                    <span class="badge bg-{{ $trendMeta['badge'] }}">
                        <i class="bi bi-{{ $trendMeta['icon'] }} me-1"></i>{{ $trendMeta['label'] }}
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Previous arrears</span>
                    <span class="fw-semibold">KES {{ number_format($indicators['previous_arrears'], 2) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Arrears recovered</span>
                    <span class="fw-semibold text-success">KES {{ number_format($indicators['arrears_recovered'], 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tenancy: deposit + move-out --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-shield-check me-2" style="color:#5926C8;"></i>Security Deposit</h6>
                    @if($deposit)
                        <a href="{{ route('landlord.deposits.show', $deposit) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                    @else
                        <a href="{{ route('landlord.deposits.index') }}" class="btn btn-sm btn-outline-primary">Deposits</a>
                    @endif
                </div>
                @if($deposit)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status</span>
                        <span class="badge kp-badge {{ $deposit->statusBadgeClass() }}">{{ $deposit->statusLabel() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Required</span>
                        <span class="fw-semibold">KES {{ number_format((float) $deposit->required_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Received</span>
                        <span class="fw-semibold text-success">KES {{ number_format((float) $deposit->received_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Outstanding</span>
                        <span class="fw-semibold {{ $deposit->outstanding() > 0 ? 'text-danger' : 'text-success' }}">
                            KES {{ number_format($deposit->outstanding(), 2) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Net refundable</span>
                        <span class="fw-semibold">KES {{ number_format($deposit->netRefundable(), 2) }}</span>
                    </div>
                @else
                    <p class="text-muted mb-0">
                        No security deposit on record. Generate deposit records from the
                        <a href="{{ route('landlord.deposits.index') }}">Deposits</a> page.
                    </p>
                @endif
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="stat-card h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="bi bi-box-arrow-right me-2" style="color:#5926C8;"></i>Notice to Vacate</h6>
                    <a href="{{ $activeMoveOut ? route('landlord.move-outs.show', $activeMoveOut) : route('landlord.move-outs.index') }}"
                       class="btn btn-sm btn-outline-primary">{{ $activeMoveOut ? 'Review' : 'Move-outs' }}</a>
                </div>
                @if($activeMoveOut)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status</span>
                        <span class="badge kp-badge {{ $activeMoveOut->statusBadgeClass() }}">{{ $activeMoveOut->statusLabelText() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Submitted</span>
                        <span class="fw-semibold">{{ $activeMoveOut->submitted_at?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Proposed move-out</span>
                        <span class="fw-semibold">{{ $activeMoveOut->proposed_move_out_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Earliest permitted</span>
                        <span class="fw-semibold">{{ $activeMoveOut->earliest_permitted_date?->format('d M Y') ?? '—' }}</span>
                    </div>
                @else
                    <p class="text-muted mb-0">No active notice to vacate for this tenant.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Ledger --}}
    <div class="stat-card mb-4">
        <h6 class="fw-bold mb-3 pb-2 border-bottom">Rental Ledger — by period</h6>
        @if($breakdown->isEmpty())
            <p class="text-muted mb-0">No rent periods recorded yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Due Date</th>
                            <th class="text-end">Charged</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($breakdown as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->period }}</td>
                                <td>{{ $row->due_date?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">KES {{ number_format($row->charged, 2) }}</td>
                                <td class="text-end text-success">KES {{ number_format($row->paid, 2) }}</td>
                                <td class="text-end {{ $row->balance > 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                                    KES {{ number_format($row->balance, 2) }}
                                </td>
                                <td>
                                    <span class="badge rounded-pill
                                        @if($row->status === 'paid') bg-success
                                        @elseif($row->status === 'overdue') bg-danger
                                        @elseif($row->status === 'partial') bg-warning text-dark
                                        @else bg-secondary
                                        @endif">
                                        {{ ucfirst($row->status) }}
                                        @if($row->is_overdue && $row->days_overdue > 0)
                                            ({{ $row->days_overdue }}d)
                                        @endif
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="2">Totals</td>
                            <td class="text-end">KES {{ number_format($summary['charged'], 2) }}</td>
                            <td class="text-end">KES {{ number_format($summary['paid'], 2) }}</td>
                            <td class="text-end">KES {{ number_format($summary['outstanding'], 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    <div class="row g-4 mb-4">
        {{-- Record payment --}}
        <div class="col-12 col-lg-6">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-cash-coin me-2 text-success"></i> Record Payment
                </h6>
                <form method="POST" action="{{ route('landlord.tenants.payments.store', $tenant) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Amount (KES)</label>
                            <input type="number" name="amount_paid" step="0.01" min="0.01"
                                   class="form-control @error('amount_paid') is-invalid @enderror"
                                   value="{{ old('amount_paid') }}" required>
                            @error('amount_paid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Payment Date</label>
                            <input type="date" name="payment_date"
                                   class="form-control @error('payment_date') is-invalid @enderror"
                                   value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Method</label>
                            <select name="method" class="form-select">
                                @foreach(['cash' => 'Cash', 'mpesa' => 'M-Pesa', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'other' => 'Other'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('method') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Reference <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="reference" maxlength="50"
                                   class="form-control @error('reference') is-invalid @enderror"
                                   value="{{ old('reference') }}" placeholder="e.g. QHK1234XYZ">
                            @error('reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Apply To</label>
                            <select name="policy" id="policy" class="form-select">
                                <option value="oldest" @selected(old('policy', config('rent.allocation_policy')) === 'oldest')>Oldest outstanding rent first</option>
                                <option value="current" @selected(old('policy') === 'current')>Current / most recent rent first</option>
                                <option value="manual" @selected(old('policy') === 'manual')>A specific rent period</option>
                            </select>
                        </div>
                        <div class="col-12" id="periodField" style="display:none;">
                            <label class="form-label fw-semibold">Rent Period</label>
                            <select name="rent_record_id" class="form-select">
                                <option value="">— select period —</option>
                                @foreach($breakdown as $row)
                                    @if($row->balance > 0)
                                        <option value="{{ $row->id }}" @selected((string) old('rent_record_id') === (string) $row->id)>
                                            {{ $row->period }} — balance KES {{ number_format($row->balance, 2) }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="notes" maxlength="500" class="form-control" value="{{ old('notes') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-check-circle me-2"></i> Record Payment
                    </button>
                </form>
            </div>
        </div>

        {{-- Issue notice --}}
        <div class="col-12 col-lg-6">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-envelope-exclamation me-2 text-warning"></i> Record Notice / Reminder
                </h6>
                <form method="POST" action="{{ route('landlord.tenants.notices.store', $tenant) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Type</label>
                            <select name="type" class="form-select" required>
                                @foreach([
                                    'payment_reminder' => 'Payment Reminder',
                                    'follow_up_reminder' => 'Follow-up Reminder',
                                    'escalation' => 'Escalation Notice',
                                    'formal_notice' => 'Formal Notice',
                                    'demand_letter' => 'Demand Letter',
                                    'other' => 'Other',
                                ] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('type') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Issued At</label>
                            <input type="date" name="issued_at" class="form-control"
                                   value="{{ old('issued_at', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Recipient <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="text" name="recipient" maxlength="255" class="form-control"
                                   value="{{ old('recipient', $tenant->email ?? $tenant->phone) }}">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Delivery</label>
                            <select name="delivery_status" class="form-select">
                                @foreach(['sent' => 'Sent', 'delivered' => 'Delivered', 'acknowledged' => 'Acknowledged', 'failed' => 'Failed', 'draft' => 'Draft'] as $val => $label)
                                    <option value="{{ $val }}" @selected(old('delivery_status') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold">Deadline <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="date" name="deadline" class="form-control" value="{{ old('deadline') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <input type="text" name="notes" maxlength="2000" class="form-control" value="{{ old('notes') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3">
                        <i class="bi bi-save me-2"></i> Record Notice
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Payment transactions --}}
    <div class="stat-card mb-4">
        <h6 class="fw-bold mb-3 pb-2 border-bottom">Payment Transactions</h6>
        @if($payments->isEmpty())
            <p class="text-muted mb-0">No payments recorded yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Applied To</th>
                            <th class="text-end">Unallocated</th>
                            <th>Reversal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                <td class="text-end fw-semibold">KES {{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->methodLabel() }}</td>
                                <td>
                                    @if($payment->reference)
                                        <span class="badge bg-light text-dark border">{{ $payment->reference }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="font-size:0.82rem;">
                                    @forelse($payment->allocations as $allocation)
                                        <div>{{ $allocation->rentRecord?->periodLabel() ?? '—' }}: KES {{ number_format($allocation->amount, 2) }}</div>
                                    @empty
                                        <span class="text-muted">Unallocated</span>
                                    @endforelse
                                </td>
                                <td class="text-end {{ $payment->unallocated_amount > 0 ? 'text-warning' : 'text-muted' }}">
                                    KES {{ number_format($payment->unallocated_amount, 2) }}
                                </td>
                                <td>
                                    @if($payment->reversals->isNotEmpty())
                                        @foreach($payment->reversals as $reversal)<span class="badge bg-light text-dark">{{ ucfirst($reversal->status) }} · KES {{ number_format($reversal->amount, 2) }}</span>@endforeach
                                    @endif
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('landlord.payments.reversals.create', $payment) }}">Request reversal</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Notices history --}}
    <div class="stat-card mb-4">
        <h6 class="fw-bold mb-3 pb-2 border-bottom">Notices &amp; Reminders</h6>
        @if($notices->isEmpty())
            <p class="text-muted mb-0">No notices recorded yet.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Issued</th>
                            <th>Type</th>
                            <th>Recipient</th>
                            <th>Deadline</th>
                            <th>Delivery</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notices as $notice)
                            <tr>
                                <td>{{ $notice->issued_at?->format('d M Y') }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $notice->typeLabel() }}</span>
                                    <small class="text-muted">(stage {{ $notice->stage }})</small>
                                </td>
                                <td>{{ $notice->recipient ?? '—' }}</td>
                                <td>{{ $notice->deadline?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $notice->deliveryStatusColor() }}">{{ ucfirst($notice->delivery_status) }}</span>
                                </td>
                                <td style="font-size:0.82rem;">{{ $notice->notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Contact + emergency --}}
    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card text-center h-100">
                <div class="kp-profile-avatar mx-auto mb-3" aria-hidden="true">
                    {{ strtoupper(substr($tenant->full_name, 0, 2)) }}
                </div>
                <h5 class="fw-bold mb-1">{{ $tenant->full_name }}</h5>
                <p class="text-muted mb-2" style="font-size:0.85rem;">{{ $tenant->phone }}</p>
                <span class="badge {{ $tenant->is_active ? 'bg-success' : 'bg-secondary' }}">
                    {{ $tenant->is_active ? 'Active Tenant' : 'Inactive' }}
                </span>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Contact Information</h6>
                <div class="mb-2">
                    <small class="text-muted d-block">Email</small>
                    <span>{{ $tenant->email ?? '—' }}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">Phone</small>
                    <span>{{ $tenant->phone }}</span>
                </div>
                <div class="mb-2">
                    <small class="text-muted d-block">National ID</small>
                    <span>{{ $tenant->national_id ?? '—' }}</span>
                </div>
                <div>
                    <small class="text-muted d-block">Move-in Date</small>
                    <span>{{ $tenant->move_in_date?->format('d M Y') ?? '—' }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Emergency Contact</h6>
                <div class="mb-2">
                    <small class="text-muted d-block">Name</small>
                    <span>{{ $tenant->emergency_contact_name ?? '—' }}</span>
                </div>
                <div>
                    <small class="text-muted d-block">Phone</small>
                    <span>{{ $tenant->emergency_contact_phone ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const policy = document.getElementById('policy');
        const field = document.getElementById('periodField');
        if (!policy || !field) return;
        const sync = () => { field.style.display = policy.value === 'manual' ? '' : 'none'; };
        policy.addEventListener('change', sync);
        sync();
    })();
</script>
@endpush

@extends('layouts.app')

@section('page-title', 'Payment Reversals')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Rent payment reversals</h1>
            <p class="kp-page-subtitle">Review accounting reversal requests and record external refund status separately.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="kp-card">
        @if($reversals->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                <p class="mb-0">No reversal requests.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Payment / tenant</th>
                            <th>Amount split</th>
                            <th>Reason and initiator</th>
                            <th>Status</th>
                            <th>External refund</th>
                            <th>Decision</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reversals as $reversal)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $reversal->payment->reference ?? '#'.$reversal->payment_id }}</div>
                                    <div>{{ $reversal->payment->tenant->full_name }}</div>
                                    <small class="text-muted">{{ $reversal->payment->tenant->property->name ?? 'Property' }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold">KSh {{ number_format($reversal->amount, 2) }}</div>
                                    <small class="text-muted d-block">Held credit: KSh {{ number_format($reversal->reversed_credit, 2) }}</small>
                                    @foreach($reversal->allocations as $line)
                                        <small class="text-muted d-block">{{ $line->paymentAllocation->rentRecord->due_date?->format('M Y') }}: KSh {{ number_format($line->amount, 2) }}</small>
                                    @endforeach
                                </td>
                                <td>
                                    <div>{{ $reversal->reason }}</div>
                                    <small class="text-muted">Initiated by {{ $reversal->initiator_name }} at {{ $reversal->initiated_at->format('Y-m-d H:i') }}</small>
                                </td>
                                <td>
                                    <span class="kp-badge {{ $reversal->status === 'approved' ? 'kp-badge-success' : ($reversal->status === 'rejected' ? 'kp-badge-danger' : 'kp-badge-warning') }}">
                                        {{ ucfirst($reversal->status) }}
                                    </span>
                                    @if($reversal->approver_name)
                                        <small class="text-muted d-block mt-1">by {{ $reversal->approver_name }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ str_replace('_', ' ', ucfirst($reversal->external_refund_status)) }}</div>
                                    @if($reversal->external_refund_reference)
                                        <small class="text-muted d-block">{{ $reversal->external_refund_reference }}</small>
                                    @endif
                                    @if($reversal->status === 'approved')
                                        <form method="POST" action="{{ route('super_admin.payment-reversals.external-refund', $reversal) }}" class="kp-inline-form mt-2">
                                            @csrf
                                            <label class="visually-hidden" for="refund-status-{{ $reversal->id }}">External refund status</label>
                                            <select id="refund-status-{{ $reversal->id }}" name="status" class="form-select form-select-sm">
                                                <option value="pending">Pending</option>
                                                <option value="confirmed">Confirmed</option>
                                                <option value="failed">Failed</option>
                                            </select>
                                            <label class="visually-hidden" for="refund-reference-{{ $reversal->id }}">Provider refund reference</label>
                                            <input id="refund-reference-{{ $reversal->id }}" name="reference" class="form-control form-control-sm" placeholder="Provider refund reference">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Record status</button>
                                        </form>
                                    @endif
                                </td>
                                <td>
                                    @if($reversal->status === 'pending')
                                        <form method="POST" action="{{ route('super_admin.payment-reversals.approve', $reversal) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary w-100">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('super_admin.payment-reversals.reject', $reversal) }}" class="kp-inline-form mt-2">
                                            @csrf
                                            <label class="visually-hidden" for="rejection-reason-{{ $reversal->id }}">Rejection reason</label>
                                            <input id="rejection-reason-{{ $reversal->id }}" name="reason" required class="form-control form-control-sm" placeholder="Rejection reason">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">Reject</button>
                                        </form>
                                    @else
                                        <span class="text-muted">Decision recorded</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($reversals->hasPages())
            <div class="mt-3">{{ $reversals->links() }}</div>
        @endif
    </div>
@endsection

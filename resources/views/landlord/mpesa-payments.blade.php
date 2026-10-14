@extends('layouts.app')

@section('page-title', 'M-Pesa Payments')


@section('content')
    <div class="mb-4">
        <h5 class="fw-bold mb-1">M-Pesa Payments</h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Review and confirm tenant rent payments made via M-Pesa.
        </p>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning mb-0">
                    {{ $pendingCount }}
                </h4>
                <small class="text-muted">Pending</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success mb-0">
                    {{ $confirmedCount }}
                </h4>
                <small class="text-muted">Confirmed</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">
                    {{ $failedCount }}
                </h4>
                <small class="text-muted">Failed</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-primary mb-0">
                    KES {{ number_format($confirmedTotal, 0) }}
                </h4>
                <small class="text-muted">Total Received</small>
            </div>
        </div>
    </div>

    <div class="stat-card">
        @if($payments->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-phone"
                   style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">
                    No M-Pesa payments yet.
                </p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Phone</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>M-Pesa Receipt</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $i => $payment)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-semibold"
                                     style="font-size:0.9rem;">
                                    {{ $payment->tenant->full_name }}
                                </div>
                                <div class="text-muted"
                                     style="font-size:0.8rem;">
                                    {{ $payment->tenant->property->name ?? '' }}
                                </div>
                            </td>
                            <td>{{ $payment->phone_number }}</td>
                            <td>
                                {{ $payment->rentRecord->monthName() }}
                                {{ $payment->rentRecord->year }}
                            </td>
                            <td class="fw-semibold">
                                KES {{ number_format($payment->amount, 2) }}
                            </td>
                            <td>
                                @if($payment->mpesa_receipt_number)
                                    <span class="badge bg-light text-dark border"
                                          style="font-size:0.8rem;">
                                        {{ $payment->mpesa_receipt_number }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $payment->statusColor() }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                                @if($payment->landlord_confirmed)
                                    <span class="badge bg-light text-success border ms-1"
                                          style="font-size:0.72rem;">
                                        <i class="bi bi-check"></i> You confirmed
                                    </span>
                                @endif
                            </td>
                            <td>
                                {{ $payment->created_at->format('d M Y H:i') }}
                            </td>
                            <td>
                                @if($payment->status === 'pending' && !$payment->landlord_confirmed)
                                    <div class="d-flex gap-2">
                                        <form method="POST"
                                              action="{{ route('landlord.mpesa-payments.confirm', $payment) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('Confirm this payment?')">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Confirm
                                            </button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('landlord.mpesa-payments.reject', $payment) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Reject this payment?')">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </div>
                                @elseif($payment->status === 'confirmed')
                                    <span class="text-success" style="font-size:0.85rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Done
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:0.82rem;">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($payments->hasPages())
        <div class="mt-3">
            {{ $payments->links() }}
        </div>
    @endif
@endsection
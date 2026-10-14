@extends('layouts.app')

@section('page-title', 'Pay Rent')


@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Pay Rent via M-Pesa</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                {{ $rent->monthName() }} {{ $rent->year }} —
                {{ $tenant->property->name }} Unit {{ $tenant->unit->unit_number }}
            </p>
        </div>
        <a href="{{ route('tenant.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-md-6">

            <!-- Rent Summary -->
            <div class="stat-card mb-4">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">
                    <i class="bi bi-receipt me-2"></i>Rent Summary
                </h6>
                <div class="row g-2">
                    <div class="col-6">
                        <small class="text-muted d-block">Amount Due</small>
                        <span class="fw-bold fs-5">
                            KES {{ number_format($rent->amount_due, 2) }}
                        </span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Already Paid</small>
                        <span class="fw-bold fs-5 text-success">
                            KES {{ number_format($rent->amount_paid, 2) }}
                        </span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Balance</small>
                        <span class="fw-bold fs-5 text-danger">
                            KES {{ number_format($rent->balance(), 2) }}
                        </span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Due Date</small>
                        <span class="fw-semibold">
                            {{ $rent->due_date->format('d M Y') }}
                        </span>
                    </div>
                </div>
            </div>

            @if($pendingPayment)
            <!-- Already has a pending payment -->
            <div class="alert alert-warning">
                <i class="bi bi-hourglass-split me-2"></i>
                You have a <strong>pending payment</strong> of
                <strong>KES {{ number_format($pendingPayment->amount, 2) }}</strong>
                initiated to <strong>{{ $pendingPayment->phone_number }}</strong>.
                <br><br>
                <a href="{{ route('tenant.payment.pending', $rent) }}"
                   class="btn btn-warning btn-sm">
                    <i class="bi bi-eye me-1"></i> Check Status
                </a>
            </div>
            @endif

            <!-- M-Pesa Payment Form -->
            <div class="stat-card">
                <div class="text-center mb-4">
                    <div style="width:70px; height:70px; background:rgba(89,38,200,0.08);
                                border-radius:50%; display:flex; align-items:center;
                                justify-content:center; margin:0 auto 12px;">
                        <i class="bi bi-phone" style="font-size:1.8rem; color:#5926C8;"></i>
                    </div>
                    <h6 class="fw-bold">Pay with M-Pesa</h6>
                    <p class="text-muted mb-0" style="font-size:0.85rem;">
                        Enter your Safaricom phone number to receive an STK Push prompt.
                    </p>
                </div>

                <form method="POST"
                      action="{{ route('tenant.pay.initiate', $rent) }}"
                      id="payForm">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            M-Pesa Phone Number
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-phone text-muted"></i>
                            </span>
                            <input type="tel" name="phone"
                                   class="form-control border-start-0 @error('phone') is-invalid @enderror"
                                   placeholder="e.g. 0712345678"
                                   value="{{ old('phone', $tenant->phone) }}"
                                   required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">
                            Must be a registered Safaricom M-Pesa number.
                        </small>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Amount to Pay (KES)
                        </label>
                        <input type="number" name="amount"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount', $rent->balance()) }}"
                               min="1"
                               max="{{ $rent->balance() }}"
                               step="0.01"
                               required>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Balance due: KES {{ number_format($rent->balance(), 2) }}
                        </small>
                    </div>

                    <!-- M-Pesa Steps -->
                    <div class="p-3 rounded-3 mb-4"
                         style="background:#f8f9ff; border:1px solid rgba(89,38,200,0.08);">
                        <p class="fw-semibold mb-2" style="font-size:0.85rem; color:#5926C8;">
                            <i class="bi bi-info-circle me-1"></i> How it works:
                        </p>
                        <ol style="font-size:0.82rem; color:#555; margin:0; padding-left:18px;">
                            <li class="mb-1">Click Pay Now below</li>
                            <li class="mb-1">A prompt will appear on your phone</li>
                            <li class="mb-1">Enter your M-Pesa PIN to confirm</li>
                            <li>Payment is recorded automatically</li>
                        </ol>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-3"
                            style="font-weight:600; font-size:1rem; border-radius:10px;"
                            id="payBtn">
                        <i class="bi bi-phone me-2"></i>
                        Pay Now via M-Pesa
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('payForm').addEventListener('submit', function () {
        const btn = document.getElementById('payBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending STK Push...';
    });
</script>
@endpush
@extends('layouts.app')

@section('page-title', 'Payment Pending')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('tenant.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">My Account</div>
    <a href="{{ route('tenant.payments') }}" class="nav-link active">
        <i class="bi bi-receipt"></i> Payment History
    </a>
    <a href="{{ route('tenant.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('tenant.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('tenant.profile') }}" class="nav-link">
        <i class="bi bi-person-circle"></i> My Profile
    </a>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="stat-card text-center py-5" id="statusCard">

                @if($payment->status === 'confirmed')
                    <!-- Confirmed -->
                    <div style="width:80px; height:80px; background:#e6f9f0;
                                border-radius:50%; display:flex; align-items:center;
                                justify-content:center; margin:0 auto 20px;">
                        <i class="bi bi-check-circle-fill"
                           style="font-size:2.5rem; color:#198754;"></i>
                    </div>
                    <h5 class="fw-bold text-success">Payment Confirmed!</h5>
                    <p class="text-muted mb-1">
                        KES {{ number_format($payment->amount, 2) }} received successfully.
                    </p>
                    @if($payment->mpesa_receipt_number)
                        <p class="text-muted" style="font-size:0.85rem;">
                            M-Pesa Receipt:
                            <strong>{{ $payment->mpesa_receipt_number }}</strong>
                        </p>
                    @endif
                    <a href="{{ route('tenant.payments') }}"
                       class="btn btn-success mt-3">
                        <i class="bi bi-receipt me-1"></i> View Payment History
                    </a>

                @elseif($payment->status === 'failed')
                    <!-- Failed -->
                    <div style="width:80px; height:80px; background:#fce4ec;
                                border-radius:50%; display:flex; align-items:center;
                                justify-content:center; margin:0 auto 20px;">
                        <i class="bi bi-x-circle-fill"
                           style="font-size:2.5rem; color:#dc3545;"></i>
                    </div>
                    <h5 class="fw-bold text-danger">Payment Failed</h5>
                    <p class="text-muted">
                        {{ $payment->result_desc ?? 'The payment was not completed.' }}
                    </p>
                    <a href="{{ route('tenant.pay', $rent) }}"
                       class="btn btn-primary mt-3">
                        <i class="bi bi-arrow-clockwise me-1"></i> Try Again
                    </a>

                @else
                    <!-- Pending -->
                    <div style="width:80px; height:80px; background:#fff3e0;
                                border-radius:50%; display:flex; align-items:center;
                                justify-content:center; margin:0 auto 20px;"
                         id="pendingIcon">
                        <i class="bi bi-hourglass-split"
                           style="font-size:2.5rem; color:#fd7e14;"></i>
                    </div>
                    <h5 class="fw-bold" id="statusTitle">Waiting for Payment</h5>
                    <p class="text-muted mb-1" id="statusDesc">
                        An M-Pesa prompt has been sent to
                        <strong>{{ $payment->phone_number }}</strong>.
                    </p>
                    <p class="text-muted" style="font-size:0.85rem;">
                        Enter your M-Pesa PIN on your phone to complete the payment.
                    </p>

                    <!-- Spinner -->
                    <div class="mt-3 mb-3" id="spinner">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Checking...</span>
                        </div>
                        <p class="text-muted mt-2" style="font-size:0.82rem;">
                            Checking payment status...
                        </p>
                    </div>

                    <div class="alert alert-light border mt-2" style="font-size:0.82rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Amount: <strong>KES {{ number_format($payment->amount, 2) }}</strong>
                        for {{ $rent->monthName() }} {{ $rent->year }}
                    </div>

                    <a href="{{ route('tenant.dashboard') }}"
                       class="btn btn-outline-secondary btn-sm mt-2">
                        <i class="bi bi-house me-1"></i> Go to Dashboard
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    @if($payment->status === 'pending')
    // Auto-check payment status every 5 seconds
    const paymentId = {{ $payment->id }};
    const rentId    = {{ $rent->id }};
    let checkCount  = 0;
    const maxChecks = 24; // 2 minutes

    function checkStatus() {
        fetch(`/tenant/payment/${paymentId}/status`)
            .then(res => res.json())
            .then(data => {
                checkCount++;

                if (data.status === 'confirmed') {
                    document.getElementById('pendingIcon').innerHTML =
                        '<i class="bi bi-check-circle-fill" style="font-size:2.5rem; color:#198754;"></i>';
                    document.getElementById('pendingIcon').style.background = '#e6f9f0';
                    document.getElementById('statusTitle').textContent = 'Payment Confirmed!';
                    document.getElementById('statusTitle').className = 'fw-bold text-success';
                    document.getElementById('statusDesc').innerHTML =
                        'KES {{ number_format($payment->amount, 2) }} received successfully.' +
                        (data.mpesa_receipt_number ? '<br><strong>' + data.mpesa_receipt_number + '</strong>' : '');
                    document.getElementById('spinner').innerHTML =
                        '<a href="/tenant/payments" class="btn btn-success mt-2">' +
                        '<i class="bi bi-receipt me-1"></i> View Payments</a>';

                } else if (data.status === 'failed') {
                    document.getElementById('pendingIcon').innerHTML =
                        '<i class="bi bi-x-circle-fill" style="font-size:2.5rem; color:#dc3545;"></i>';
                    document.getElementById('pendingIcon').style.background = '#fce4ec';
                    document.getElementById('statusTitle').textContent = 'Payment Failed';
                    document.getElementById('statusTitle').className = 'fw-bold text-danger';
                    document.getElementById('statusDesc').textContent =
                        data.result_desc || 'The payment was not completed.';
                    document.getElementById('spinner').innerHTML =
                        '<a href="/tenant/pay/' + rentId + '" class="btn btn-primary mt-2">' +
                        '<i class="bi bi-arrow-clockwise me-1"></i> Try Again</a>';

                } else if (checkCount < maxChecks) {
                    setTimeout(checkStatus, 5000);
                } else {
                    document.getElementById('spinner').innerHTML =
                        '<p class="text-muted" style="font-size:0.82rem;">Still waiting...' +
                        ' <a href="">Refresh</a> to check again.</p>';
                }
            })
            .catch(() => {
                if (checkCount < maxChecks) {
                    setTimeout(checkStatus, 5000);
                }
            });
    }

    // Start checking after 5 seconds
    setTimeout(checkStatus, 5000);
    @endif
</script>
@endpush
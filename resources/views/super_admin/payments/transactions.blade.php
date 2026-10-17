@extends('layouts.app')

@section('page-title', 'Transactions')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Transactions</h1>
            <p class="kp-page-subtitle">Monitor M-Pesa transactions across all tenants.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.payments.export', array_filter($filters)) }}" class="btn btn-outline-primary">
                <i class="bi bi-download me-2"></i> Export CSV
            </a>
            <a href="{{ route('super_admin.payments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Payment Settings
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-4">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-receipt"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($totals['count']) }}</div>
                <p class="kp-kpi-label">Matching transactions</p>
            </div>
        </div>
        <div class="col-6 col-xl-4">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-cash-stack"></i></div></div>
                <div class="kp-kpi-value">KES {{ number_format($totals['amount'], 0) }}</div>
                <p class="kp-kpi-label">Matching value</p>
            </div>
        </div>
    </div>

    <div class="kp-card mb-4">
        <form method="GET" action="{{ route('super_admin.payments.transactions') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label fw-semibold">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control"
                       placeholder="Receipt number or phone number">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $value => $label)
                        <option value="{{ $value }}" {{ $filters['status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if($filters['q'] !== '' || $filters['status'] !== '')
                    <a href="{{ route('super_admin.payments.transactions') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="kp-card">
        @if($payments->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-phone"></i>
                <p class="mb-0">No transactions match your filters.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Receipt</th>
                            <th>Tenant</th>
                            <th>Phone</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Landlord</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $index => $payment)
                            <tr>
                                <td>{{ $payments->firstItem() + $index }}</td>
                                <td class="fw-semibold">{{ $payment->mpesa_receipt_number ?: '—' }}</td>
                                <td>{{ $payment->tenant?->full_name ?? '—' }}</td>
                                <td>{{ $payment->phone_number }}</td>
                                <td class="text-end">KES {{ number_format((float) $payment->amount, 0) }}</td>
                                <td>
                                    @php $badge = $payment->statusColor() === 'secondary' ? 'muted' : $payment->statusColor(); @endphp
                                    <span class="kp-badge kp-badge-{{ $badge }}">{{ ucfirst($payment->status) }}</span>
                                </td>
                                <td>
                                    @if($payment->landlord_confirmed)
                                        <i class="bi bi-check-circle text-success" title="Confirmed"></i>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-muted" style="font-size:0.82rem;">{{ $payment->created_at?->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div class="mt-3">{{ $payments->links() }}</div>
            @endif
        @endif
    </div>
@endsection

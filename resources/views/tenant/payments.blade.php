@extends('layouts.app')

@section('page-title', 'Payment History')


@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Payment History</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Ledger and transactions for
                {{ $tenant->property->name }} — Unit {{ $tenant->unit->unit_number }}
            </p>
        </div>
        <span class="badge rounded-pill px-3 py-2"
              style="background:{{ $summary['status']['color'] }}; font-size:0.8rem;">
            {{ $summary['status']['label'] }}
        </span>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-primary mb-0">{{ $totalRecords }}</h4>
                <small class="text-muted">Rent Periods</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success mb-0">KES {{ number_format($summary['paid'], 2) }}</h4>
                <small class="text-muted">Total Paid</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">KES {{ number_format($summary['outstanding'], 2) }}</h4>
                <small class="text-muted">Outstanding</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold mb-0" style="color:#E99B20;">KES {{ number_format($summary['arrears'], 2) }}</h4>
                <small class="text-muted">Arrears</small>
            </div>
        </div>
    </div>

    {{-- Ledger by period --}}
    <div class="stat-card mb-4">
        <h6 class="fw-bold mb-3 pb-2 border-bottom">Rent Ledger — by period</h6>
        @if($ledger->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-receipt" style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted mb-0">No rent periods found.</p>
            </div>
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
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ledger as $row)
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
                                <td class="text-end">
                                    @if($row->balance > 0)
                                        <a href="{{ route('tenant.pay', $row->record) }}"
                                           class="btn btn-sm btn-success" style="white-space:nowrap;">
                                            <i class="bi bi-phone me-1"></i> Pay
                                        </a>
                                    @else
                                        <span class="text-success" style="font-size:0.82rem;">
                                            <i class="bi bi-check-circle-fill me-1"></i> Paid
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Payment transactions --}}
    <div class="stat-card">
        <h6 class="fw-bold mb-3 pb-2 border-bottom">Transactions</h6>
        @if($payments->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-receipt" style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">No individual payments recorded yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Applied To</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $index => $payment)
                            <tr>
                                <td>{{ $payments->firstItem() + $index }}</td>
                                <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                                <td class="text-end fw-semibold">KES {{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->methodLabel() }}</td>
                                <td>
                                    @if($payment->reference)
                                        <span class="badge bg-light text-dark border" style="font-size:0.78rem;">
                                            {{ $payment->reference }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="font-size:0.82rem;">
                                    @forelse($payment->allocations as $allocation)
                                        <div>{{ $allocation->rentRecord?->periodLabel() ?? '—' }}: KES {{ number_format($allocation->amount, 2) }}</div>
                                    @empty
                                        <span class="text-muted">Unallocated credit</span>
                                    @endforelse
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

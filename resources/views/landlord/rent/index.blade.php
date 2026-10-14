@extends('layouts.app')

@section('page-title', 'Rent Payments')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Rent Payments</h1>
            <p class="kp-page-subtitle">Track rent records, collections and outstanding balances.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.rent.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Add Rent Record
            </a>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-cash-stack"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($totalExpected, 0) }}</div>
                <p class="kp-kpi-label">Total Expected</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-check-circle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($totalCollected, 0) }}</div>
                <p class="kp-kpi-label">Total Collected</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-hourglass-split"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem;">KES {{ number_format($totalOutstanding, 0) }}</div>
                <p class="kp-kpi-label">Outstanding</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon red"><i class="bi bi-exclamation-triangle"></i></div></div>
                <div class="kp-kpi-value" style="font-size:1.35rem; color:var(--kp-danger);">KES {{ number_format($totalOverdue, 0) }}</div>
                <p class="kp-kpi-label">Overdue</p>
            </div>
        </div>
    </div>

    <!-- Bulk Generate -->
    <div class="kp-card mb-4">
        <div class="kp-card-header">
            <div>
                <h2 class="kp-card-title"><i class="bi bi-lightning-charge me-2" style="color:var(--kp-gold);"></i>Bulk Generate Rent Records</h2>
                <p class="kp-card-subtitle">Create this period's rent records for all active tenants.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('landlord.rent.generate-bulk') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-6 col-md-3">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$m,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    @for($y = date('Y'); $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Due Day</label>
                <input type="number" name="day" class="form-control"
                       value="{{ config('rent.due_day', 3) }}" min="1" max="28">
            </div>
            <div class="col-6 col-md-3">
                <button type="submit" class="btn btn-gold w-100"
                        onclick="return confirm('Generate rent records for all active tenants?')">
                    <i class="bi bi-lightning-charge me-2"></i> Generate
                </button>
            </div>
            <div class="col-12 col-md-2">
                <a href="{{ route('landlord.rent.create') }}" class="btn btn-outline-primary w-100">
                    <i class="bi bi-plus-circle me-2"></i> Add Single
                </a>
            </div>
        </form>
    </div>

    <!-- Filters -->
    <div class="kp-card mb-4">
        <form method="GET" action="{{ route('landlord.rent.index') }}" class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="paid"     {{ request('status') === 'paid'     ? 'selected' : '' }}>Paid</option>
                    <option value="partial"  {{ request('status') === 'partial'  ? 'selected' : '' }}>Partial</option>
                    <option value="overdue"  {{ request('status') === 'overdue'  ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label">Property</label>
                <select name="property_id" class="form-select">
                    <option value="">All Properties</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}"
                            {{ request('property_id') == $property->id ? 'selected' : '' }}>
                            {{ $property->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                    <option value="">All Months</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$m,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('landlord.rent.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Rent Records Table -->
    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Rent Records</h2>
            <span class="badge kp-badge-muted">{{ $rentRecords->total() }} records</span>
        </div>

        @if($rentRecords->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-cash-coin"></i>
                <p class="mb-3">No rent records found. Use Bulk Generate to create records for all tenants.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Unit</th>
                            <th>Period</th>
                            <th>Amount Due</th>
                            <th>Amount Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rentRecords as $index => $rent)
                        <tr>
                            <td class="text-muted">{{ $rentRecords->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-semibold" style="font-size:0.9rem;">{{ $rent->tenant->full_name }}</div>
                                <div class="text-muted" style="font-size:0.78rem;">{{ $rent->property->name }}</div>
                            </td>
                            <td><span class="badge kp-badge-purple">{{ $rent->unit->unit_number }}</span></td>
                            <td>{{ $rent->monthName() }} {{ $rent->year }}</td>
                            <td>KES {{ number_format($rent->amount_due, 2) }}</td>
                            <td>KES {{ number_format($rent->amount_paid, 2) }}</td>
                            <td>
                                @if($rent->balance() > 0)
                                    <span class="fw-semibold" style="color:var(--kp-danger);">KES {{ number_format($rent->balance(), 2) }}</span>
                                @else
                                    <span class="fw-semibold" style="color:var(--kp-success);">KES 0.00</span>
                                @endif
                            </td>
                            <td>{{ $rent->due_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge
                                    @if($rent->status === 'paid') kp-badge-success
                                    @elseif($rent->status === 'partial') kp-badge-warning
                                    @elseif($rent->status === 'overdue') kp-badge-danger
                                    @else kp-badge-muted
                                    @endif">
                                    {{ ucfirst($rent->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('landlord.rent.show', $rent) }}"
                                       class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($rent->status !== 'paid')
                                    <form method="POST" action="{{ route('landlord.rent.overdue', $rent) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Mark Overdue">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($rentRecords->hasPages())
                <div class="mt-3">{{ $rentRecords->links() }}</div>
            @endif
        @endif
    </div>
@endsection

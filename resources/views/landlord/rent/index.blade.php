@extends('layouts.app')

@section('page-title', 'Rent Management')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}" class="nav-link">
        <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}" class="nav-link active">
        <i class="bi bi-cash-coin"></i> Rent
    </a>
    <a href="{{ route('landlord.mpesa-payments') }}" class="nav-link">
    <i class="bi bi-phone"></i> M-Pesa Payments
</a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('landlord.reports.index') }}" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')

    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total Expected</p>
                        <h3 class="stat-value" style="font-size:1.4rem;">
                            KES {{ number_format($totalExpected, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#e8f0fe;">
                        <i class="bi bi-cash-stack" style="color:#0f3460;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total Collected</p>
                        <h3 class="stat-value" style="font-size:1.4rem;">
                            KES {{ number_format($totalCollected, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#e6f9f0;">
                        <i class="bi bi-check-circle" style="color:#198754;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Outstanding</p>
                        <h3 class="stat-value" style="font-size:1.4rem;">
                            KES {{ number_format($totalOutstanding, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#fff3e0;">
                        <i class="bi bi-hourglass-split" style="color:#fd7e14;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Overdue</p>
                        <h3 class="stat-value" style="font-size:1.4rem;">
                            KES {{ number_format($totalOverdue, 2) }}
                        </h3>
                    </div>
                    <div class="stat-icon" style="background:#fce4ec;">
                        <i class="bi bi-exclamation-triangle" style="color:#e91e63;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Generate + Add -->
    <div class="stat-card mb-4">
        <h6 class="fw-bold mb-3">
            <i class="bi bi-lightning-charge me-2 text-warning"></i>
            Bulk Generate Rent Records
        </h6>
        <form method="POST" action="{{ route('landlord.rent.generate-bulk') }}"
              class="row g-3 align-items-end">
            @csrf
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Month</label>
                <select name="month" class="form-select">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $m == date('n') ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$m,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Year</label>
                <select name="year" class="form-select">
                    @for($y = date('Y'); $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Due Day</label>
                <input type="number" name="day" class="form-control"
                       value="5" min="1" max="28" placeholder="e.g. 5">
            </div>
            <div class="col-6 col-md-3">
                <button type="submit" class="btn btn-warning w-100"
                        onclick="return confirm('Generate rent records for all active tenants?')">
                    <i class="bi bi-lightning-charge me-2"></i> Generate
                </button>
            </div>
            <div class="col-12 col-md-2">
                <a href="{{ route('landlord.rent.create') }}" class="btn btn-primary w-100">
                    <i class="bi bi-plus-circle me-2"></i> Add Single
                </a>
            </div>
        </form>
    </div>

    <!-- Filters -->
    <div class="stat-card mb-4">
        <form method="GET" action="{{ route('landlord.rent.index') }}"
              class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="paid"     {{ request('status') === 'paid'     ? 'selected' : '' }}>Paid</option>
                    <option value="partial"  {{ request('status') === 'partial'  ? 'selected' : '' }}>Partial</option>
                    <option value="overdue"  {{ request('status') === 'overdue'  ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Property</label>
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
                <label class="form-label fw-semibold">Month</label>
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
                <label class="form-label fw-semibold">Year</label>
                <select name="year" class="form-select">
                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('landlord.rent.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Rent Records Table -->
    <div class="stat-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0">Rent Records</h6>
            <span class="badge bg-light text-dark border">
                {{ $rentRecords->count() }} records
            </span>
        </div>

        @if($rentRecords->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-cash-coin" style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">No rent records found. Use Bulk Generate to create records for all tenants.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rentRecords as $index => $rent)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-semibold" style="font-size:0.9rem;">
                                    {{ $rent->tenant->full_name }}
                                </div>
                                <div class="text-muted" style="font-size:0.8rem;">
                                    {{ $rent->property->name }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $rent->unit->unit_number }}
                                </span>
                            </td>
                            <td>{{ $rent->monthName() }} {{ $rent->year }}</td>
                            <td>KES {{ number_format($rent->amount_due, 2) }}</td>
                            <td>KES {{ number_format($rent->amount_paid, 2) }}</td>
                            <td>
                                @if($rent->balance() > 0)
                                    <span class="text-danger fw-semibold">
                                        KES {{ number_format($rent->balance(), 2) }}
                                    </span>
                                @else
                                    <span class="text-success fw-semibold">0.00</span>
                                @endif
                            </td>
                            <td>{{ $rent->due_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge rounded-pill
                                    @if($rent->status === 'paid') bg-success
                                    @elseif($rent->status === 'partial') bg-warning text-dark
                                    @elseif($rent->status === 'overdue') bg-danger
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($rent->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('landlord.rent.show', $rent) }}"
                                       class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($rent->status !== 'paid')
                                    <form method="POST"
                                          action="{{ route('landlord.rent.overdue', $rent) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Mark Overdue">
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
        @endif
    </div>
@endsection
@extends('layouts.app')

@section('page-title', 'Rent Collection Report')

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
    <a href="{{ route('landlord.rent.index') }}" class="nav-link">
        <i class="bi bi-cash-coin"></i> Rent
    </a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('landlord.reports.index') }}" class="nav-link active">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Rent Collection Report</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Filter by month, year, and property.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('landlord.reports.export.excel', 'rent-collection') }}?{{ http_build_query(request()->all()) }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <a href="{{ route('landlord.reports.export.pdf', 'rent-collection') }}?{{ http_build_query(request()->all()) }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
            <a href="{{ route('landlord.reports.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Summary -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="stat-card text-center">
                <p class="stat-label">Total Expected</p>
                <h4 class="fw-bold text-primary">
                    KES {{ number_format($totalExpected, 2) }}
                </h4>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card text-center">
                <p class="stat-label">Total Collected</p>
                <h4 class="fw-bold text-success">
                    KES {{ number_format($totalCollected, 2) }}
                </h4>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card text-center">
                <p class="stat-label">Outstanding</p>
                <h4 class="fw-bold text-danger">
                    KES {{ number_format($totalOutstanding, 2) }}
                </h4>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="stat-card mb-4">
        <form method="GET"
              action="{{ route('landlord.reports.rent-collection') }}"
              class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Month</label>
                <select name="month" class="form-select">
                    <option value="">All Months</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}"
                            {{ request('month') == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0,0,0,$m,1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Year</label>
                <select name="year" class="form-select">
                    <option value="">All Years</option>
                    @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}"
                            {{ request('year') == $y ? 'selected' : '' }}>
                            {{ $y }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Property</label>
                <select name="property_id" class="form-select">
                    <option value="">All Properties</option>
                    @foreach($properties as $p)
                        <option value="{{ $p->id }}"
                            {{ request('property_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="paid"     {{ request('status') === 'paid'     ? 'selected' : '' }}>Paid</option>
                    <option value="pending"  {{ request('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="partial"  {{ request('status') === 'partial'  ? 'selected' : '' }}>Partial</option>
                    <option value="overdue"  {{ request('status') === 'overdue'  ? 'selected' : '' }}>Overdue</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('landlord.reports.rent-collection') }}"
                   class="btn btn-outline-secondary">
                    <i class="bi bi-x"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="stat-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0">Results</h6>
            <span class="badge bg-light text-dark border">
                {{ $records->count() }} records
            </span>
        </div>

        @if($records->isEmpty())
            <div class="text-center py-4">
                <i class="bi bi-search" style="font-size:2rem; color:#dee2e6;"></i>
                <p class="mt-2 text-muted">No records found for the selected filters.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0"
                       style="font-size:0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th>Period</th>
                            <th>Amount Due</th>
                            <th>Amount Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($records as $i => $r)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $r->tenant->full_name }}</td>
                            <td>
                                <div>{{ $r->property->name }}</div>
                                <small class="text-muted">
                                    Unit {{ $r->unit->unit_number }}
                                </small>
                            </td>
                            <td>{{ $r->monthName() }} {{ $r->year }}</td>
                            <td>KES {{ number_format($r->amount_due, 2) }}</td>
                            <td>KES {{ number_format($r->amount_paid, 2) }}</td>
                            <td class="{{ $r->balance() > 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                                KES {{ number_format($r->balance(), 2) }}
                            </td>
                            <td>{{ $r->due_date->format('d M Y') }}</td>
                            <td>
                                <span class="badge rounded-pill
                                    @if($r->status === 'paid') bg-success
                                    @elseif($r->status === 'partial') bg-warning text-dark
                                    @elseif($r->status === 'overdue') bg-danger
                                    @else bg-secondary
                                    @endif">
                                    {{ ucfirst($r->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4">Totals</td>
                            <td>KES {{ number_format($totalExpected, 2) }}</td>
                            <td>KES {{ number_format($totalCollected, 2) }}</td>
                            <td class="text-danger">
                                KES {{ number_format($totalOutstanding, 2) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>
@endsection
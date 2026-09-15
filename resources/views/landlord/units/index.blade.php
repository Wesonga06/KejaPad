@extends('layouts.app')

@section('page-title', 'Units — ' . $property->name)

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link active">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}" class="nav-link">
    <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}" class="nav-link">
    <i class="bi bi-cash-coin"></i> Rent
    </a>

    <div class="nav-section-title">Other</div>
    <a href="#" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('landlord.properties.index') }}">Properties</a>
            </li>
            <li class="breadcrumb-item active">{{ $property->name }} — Units</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">{{ $property->name }} — Units</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                <i class="bi bi-geo-alt me-1"></i>{{ $property->location }}
            </p>
        </div>
        <a href="{{ route('landlord.units.create', $property) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> Add Unit
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-primary mb-0">{{ $units->count() }}</h4>
                <small class="text-muted">Total Units</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success mb-0">{{ $units->where('status', 'vacant')->count() }}</h4>
                <small class="text-muted">Vacant</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">{{ $units->where('status', 'occupied')->count() }}</h4>
                <small class="text-muted">Occupied</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning mb-0">{{ $units->where('status', 'reserved')->count() }}</h4>
                <small class="text-muted">Reserved</small>
            </div>
        </div>
    </div>

    @if($units->isEmpty())
        <div class="stat-card text-center py-5">
            <i class="bi bi-door-open" style="font-size:3rem; color:#dee2e6;"></i>
            <p class="mt-3 text-muted">No units added yet. Start by adding a unit.</p>
            <a href="{{ route('landlord.units.create', $property) }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle me-2"></i> Add Unit
            </a>
        </div>
    @else
        <div class="stat-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Unit Number</th>
                            <th>Rent Amount</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($units as $index => $unit)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><span class="fw-semibold">{{ $unit->unit_number }}</span></td>
                            <td>KES {{ number_format($unit->rent_amount, 2) }}</td>
                            <td>
                                <span class="badge rounded-pill
                                    @if($unit->status === 'vacant') bg-success
                                    @elseif($unit->status === 'occupied') bg-danger
                                    @else bg-warning text-dark
                                    @endif">
                                    {{ ucfirst($unit->status) }}
                                </span>
                            </td>
                            <td>{{ $unit->description ? Str::limit($unit->description, 40) : '—' }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('landlord.units.edit', [$property, $unit]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                          action="{{ route('landlord.units.destroy', [$property, $unit]) }}"
                                          onsubmit="return confirm('Delete this unit?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
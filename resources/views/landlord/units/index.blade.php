@extends('layouts.app')

@section('page-title', 'Units — ' . $property->name)


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

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">{{ $property->name }} — Units</h1>
            <p class="kp-page-subtitle">
                <i class="bi bi-geo-alt me-1"></i>{{ $property->location }}
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.passport.index', $property) }}" class="btn btn-outline-primary">
                <i class="bi bi-journal-text me-2"></i> Property Passports
            </a>
            @if($remainingCapacity > 0)
                <a href="{{ route('landlord.units.create', $property) }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i> Add Unit
                </a>
            @endif
        </div>
    </div>

    <div class="kp-card kp-capacity-indicator mb-4" role="status">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <strong>Registered units: {{ $totalUnits }} of {{ $property->total_units }}</strong>
            @if($remainingCapacity > 0)
                <span class="text-muted">{{ $remainingCapacity }} {{ \Illuminate\Support\Str::plural('unit', $remainingCapacity) }} remaining</span>
            @else
                <span class="text-danger fw-semibold">
                    @if($totalUnits > $property->total_units)
                        This property is over its listed capacity. No more units can be added.
                    @else
                        Listed unit capacity reached. No more units can be added.
                    @endif
                </span>
            @endif
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-primary mb-0">{{ $totalUnits }}</h4>
                <small class="text-muted">Total Units</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success mb-0">{{ $vacantCount }}</h4>
                <small class="text-muted">Vacant</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">{{ $occupiedCount }}</h4>
                <small class="text-muted">Occupied</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning mb-0">{{ $reservedCount }}</h4>
                <small class="text-muted">Reserved</small>
            </div>
        </div>
    </div>

    @if($units->isEmpty())
        <div class="stat-card text-center py-5">
            <i class="bi bi-door-open" style="font-size:3rem; color:#dee2e6;"></i>
            <p class="mt-3 text-muted">No units added yet. Start by adding a unit.</p>
            @if($remainingCapacity > 0)
                <a href="{{ route('landlord.units.create', $property) }}" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle me-2"></i> Add Unit
                </a>
            @endif
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
                            <td>{{ $units->firstItem() + $index }}</td>
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

        @if($units->hasPages())
            <div class="mt-3">
                {{ $units->links() }}
            </div>
        @endif
    @endif
@endsection

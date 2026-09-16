@extends('layouts.app')

@section('page-title', 'My Properties')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link active">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-door-open"></i> Units
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
    <a href="{{ route('landlord.reports.index') }}" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">My Properties</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Manage all your registered properties.</p>
        </div>
        <a href="{{ route('landlord.properties.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> Add Property
        </a>
    </div>

    @if($properties->isEmpty())
        <div class="stat-card text-center py-5">
            <i class="bi bi-buildings" style="font-size:3rem; color:#dee2e6;"></i>
            <p class="mt-3 text-muted">No properties yet. Add your first property.</p>
            <a href="{{ route('landlord.properties.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle me-2"></i> Add Property
            </a>
        </div>
    @else
        <div class="row g-4">
            @foreach($properties as $property)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="stat-card h-100">
                    <!-- Property Photo -->
                    @if($property->photo)
                        <img src="{{ asset('storage/' . $property->photo) }}"
                             alt="{{ $property->name }}"
                             class="w-100 rounded-2 mb-3"
                             style="height:160px; object-fit:cover;">
                    @else
                        <div class="w-100 rounded-2 mb-3 d-flex align-items-center justify-content-center"
                             style="height:160px; background:#f0f2f5;">
                            <i class="bi bi-buildings" style="font-size:2.5rem; color:#adb5bd;"></i>
                        </div>
                    @endif

                    <!-- Property Info -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">{{ $property->name }}</h6>
                            <p class="text-muted mb-1" style="font-size:0.85rem;">
                                <i class="bi bi-geo-alt me-1"></i>{{ $property->location }}
                            </p>
                            <p class="text-muted mb-2" style="font-size:0.85rem;">
                                <i class="bi bi-door-open me-1"></i>{{ $property->total_units }} Units
                            </p>
                        </div>
                        <span class="badge {{ $property->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($property->status) }}
                        </span>
                    </div>

                    @if($property->description)
                        <p class="text-muted mb-3" style="font-size:0.82rem;">
                            {{ Str::limit($property->description, 80) }}
                        </p>
                    @endif

                    <!-- Actions -->
                    <div class="d-flex gap-2 mt-auto">
                        <a href="{{ route('landlord.units.index', $property) }}"
                            class="btn btn-sm btn-outline-secondary flex-fill">
                                <i class="bi bi-door-open me-1"></i> Units
                        </a>
                        <a href="{{ route('landlord.properties.edit', $property) }}"
                            class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('landlord.properties.destroy', $property) }}"
                              onsubmit="return confirm('Delete this property?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
@endsection
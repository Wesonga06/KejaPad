@extends('layouts.app')

@section('page-title', 'Properties')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Properties</h1>
            <p class="kp-page-subtitle">Manage all your registered properties.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.properties.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Add Property
            </a>
        </div>
    </div>

    @if($properties->isEmpty())
        <div class="kp-card kp-empty">
            <i class="bi bi-buildings"></i>
            <p class="mb-3">No properties yet. Add your first property to begin.</p>
            <a href="{{ route('landlord.properties.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Add Property
            </a>
        </div>
    @else
        <div class="row g-4">
            @foreach($properties as $property)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="stat-card h-100 d-flex flex-column">
                    @if($property->photo)
                        <img src="{{ asset('storage/' . $property->photo) }}"
                             alt="{{ $property->name }}"
                             class="kp-property-image rounded-3 mb-3">
                    @else
                        <div class="kp-property-image kp-property-placeholder rounded-3 mb-3 d-flex align-items-center justify-content-center">
                            <i class="bi bi-buildings" style="font-size:2.5rem; opacity:.6;"></i>
                        </div>
                    @endif

                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <h3 class="fw-bold mb-1" style="font-size:1.05rem; color:var(--kp-deep);">{{ $property->name }}</h3>
                            <p class="text-muted mb-1" style="font-size:0.85rem;">
                                <i class="bi bi-geo-alt me-1"></i>{{ $property->location }}
                            </p>
                            <p class="text-muted mb-2" style="font-size:0.85rem;">
                                <i class="bi bi-door-open me-1"></i>Registered units: {{ $property->units_count }} of {{ $property->total_units }}
                            </p>
                            @if($property->units_count > $property->total_units)
                                <p class="text-danger fw-semibold mb-2" style="font-size:0.8rem;">Over listed capacity. Additional units are blocked.</p>
                            @else
                                <p class="text-muted mb-2" style="font-size:0.8rem;">
                                    {{ max(0, $property->total_units - $property->units_count) }} {{ \Illuminate\Support\Str::plural('unit', max(0, $property->total_units - $property->units_count)) }} remaining
                                </p>
                            @endif
                        </div>
                        <span class="badge {{ $property->status === 'active' ? 'kp-badge-success' : 'kp-badge-muted' }}">
                            {{ ucfirst($property->status) }}
                        </span>
                    </div>

                    @if($property->description)
                        <p class="text-muted mb-3" style="font-size:0.82rem;">
                            {{ Str::limit($property->description, 80) }}
                        </p>
                    @endif

                    <div class="d-flex gap-2 mt-auto pt-2">
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

    @if($properties->hasPages())
        <div class="mt-3">{{ $properties->links() }}</div>
    @endif
@endsection

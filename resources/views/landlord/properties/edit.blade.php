@extends('layouts.app')

@section('page-title', 'Edit Property')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link active">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.units.index') }}" class="nav-link">
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
            <h5 class="fw-bold mb-1">Edit Property</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Update the details for {{ $property->name }}.</p>
        </div>
        <a href="{{ route('landlord.properties.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:650px;">
        <form method="POST" action="{{ route('landlord.properties.update', $property) }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Property Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $property->name) }}">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Location</label>
                <input type="text" name="location" class="form-control @error('location') is-invalid @enderror"
                       value="{{ old('location', $property->location) }}">
                @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $property->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Total Units</label>
                    <input type="number" name="total_units" class="form-control @error('total_units') is-invalid @enderror"
                           value="{{ old('total_units', $property->total_units) }}" min="1">
                    @error('total_units') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active" {{ $property->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $property->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Property Photo <span class="text-muted fw-normal">(optional)</span></label>

                @if($property->photo)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $property->photo) }}"
                             alt="Current photo" class="rounded-2"
                             style="height:100px; object-fit:cover;">
                        <small class="text-muted d-block mt-1">Current photo. Upload a new one to replace it.</small>
                    </div>
                @endif

                <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror"
                       accept="image/*">
                @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save me-2"></i> Save Changes
            </button>
        </form>
    </div>
@endsection
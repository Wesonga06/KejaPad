@extends('layouts.app')

@section('page-title', 'Add Unit')

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
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('landlord.properties.index') }}">Properties</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('landlord.units.index', $property) }}">{{ $property->name }}</a>
            </li>
            <li class="breadcrumb-item active">Add Unit</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Add Unit to {{ $property->name }}</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Fill in the unit details below.</p>
        </div>
        <a href="{{ route('landlord.units.index', $property) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:600px;">
        <form method="POST" action="{{ route('landlord.units.store', $property) }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Unit Number</label>
                <input type="text" name="unit_number"
                       class="form-control @error('unit_number') is-invalid @enderror"
                       value="{{ old('unit_number') }}" placeholder="e.g. A1, 101, Ground Floor">
                @error('unit_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Rent Amount (KES)</label>
                <input type="number" name="rent_amount"
                       class="form-control @error('rent_amount') is-invalid @enderror"
                       value="{{ old('rent_amount') }}" placeholder="e.g. 15000" min="0" step="0.01">
                @error('rent_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="vacant" {{ old('status') === 'vacant' ? 'selected' : '' }}>Vacant</option>
                    <option value="occupied" {{ old('status') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                    <option value="reserved" {{ old('status') === 'reserved' ? 'selected' : '' }}>Reserved</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="e.g. 2 bedroom, self-contained">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-door-open me-2"></i> Save Unit
            </button>
        </form>
    </div>
@endsection
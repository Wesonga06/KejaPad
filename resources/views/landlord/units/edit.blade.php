@extends('layouts.app')

@section('page-title', 'Edit Unit')


@section('content')
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('landlord.properties.index') }}">Properties</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('landlord.units.index', $property) }}">{{ $property->name }}</a>
            </li>
            <li class="breadcrumb-item active">Edit Unit {{ $unit->unit_number }}</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Edit Unit {{ $unit->unit_number }}</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">{{ $property->name }} — {{ $property->location }}</p>
        </div>
        <a href="{{ route('landlord.units.index', $property) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:600px;">
        <form method="POST" action="{{ route('landlord.units.update', [$property, $unit]) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Unit Number</label>
                <input type="text" name="unit_number"
                       class="form-control @error('unit_number') is-invalid @enderror"
                       value="{{ old('unit_number', $unit->unit_number) }}">
                @error('unit_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Rent Amount (KES)</label>
                <input type="number" name="rent_amount"
                       class="form-control @error('rent_amount') is-invalid @enderror"
                       value="{{ old('rent_amount', $unit->rent_amount) }}" min="0" step="0.01">
                @error('rent_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="vacant" {{ $unit->status === 'vacant' ? 'selected' : '' }}>Vacant</option>
                    <option value="occupied" {{ $unit->status === 'occupied' ? 'selected' : '' }}>Occupied</option>
                    <option value="reserved" {{ $unit->status === 'reserved' ? 'selected' : '' }}>Reserved</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="description" rows="3"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $unit->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save me-2"></i> Save Changes
            </button>
        </form>
    </div>
@endsection
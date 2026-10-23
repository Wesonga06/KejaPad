@extends('layouts.app')

@section('page-title', 'Add Unit')


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

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Add Unit to {{ $property->name }}</h1>
            <p class="kp-page-subtitle">Fill in the unit details below.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.units.index', $property) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="kp-card kp-capacity-indicator mb-4" style="max-width:600px;" role="status">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <strong>Registered units: {{ $registeredUnits }} of {{ $property->total_units }}</strong>
            @if($remainingCapacity > 0)
                <span class="text-muted">{{ $remainingCapacity }} {{ \Illuminate\Support\Str::plural('unit', $remainingCapacity) }} remaining</span>
            @elseif($registeredUnits > $property->total_units)
                <span class="text-danger fw-semibold">This property is over its listed capacity. No more units can be added.</span>
            @else
                <span class="text-danger fw-semibold">Listed unit capacity reached. No more units can be added.</span>
            @endif
        </div>
    </div>

    <div class="stat-card" style="max-width:600px;">
        @error('capacity')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
        @enderror
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

            <button type="submit" class="btn btn-primary w-100" @disabled($remainingCapacity === 0)>
                <i class="bi bi-door-open me-2"></i> Save Unit
            </button>
        </form>
    </div>
@endsection

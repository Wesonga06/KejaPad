@extends('layouts.app')

@section('page-title', 'Add Property')


@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Add New Property</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Fill in the details below to register a property.</p>
        </div>
        <a href="{{ route('landlord.properties.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:650px;">
        <form method="POST" action="{{ route('landlord.properties.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Property Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" placeholder="e.g. Sunrise Apartments">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Location</label>
                <input type="text" name="location" class="form-control @error('location') is-invalid @enderror"
                       value="{{ old('location') }}" placeholder="e.g. Westlands, Nairobi">
                @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description <span class="text-muted fw-normal">(optional)</span></label>
                <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror"
                          placeholder="Brief description of the property">{{ old('description') }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Total Units</label>
                    <input type="number" name="total_units" class="form-control @error('total_units') is-invalid @enderror"
                           value="{{ old('total_units') }}" placeholder="e.g. 20" min="1">
                    @error('total_units') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Property Photo <span class="text-muted fw-normal">(optional)</span></label>
                <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror"
                       accept="image/*">
                @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Caretakers <span class="text-muted fw-normal">(optional)</span></label>
                <select name="caretakers[]" class="form-select @error('caretakers') is-invalid @enderror" multiple size="4">
                    @foreach($caretakers as $caretaker)
                        <option value="{{ $caretaker->id }}" {{ in_array($caretaker->id, old('caretakers', [])) ? 'selected' : '' }}>
                            {{ $caretaker->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Hold Ctrl (Cmd on Mac) to select multiple. Assigned caretakers will see maintenance requests for this property.</div>
                @error('caretakers') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if($caretakers->isEmpty())
                    <div class="form-text text-muted">No caretakers available yet.</div>
                @endif
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-buildings me-2"></i> Save Property
            </button>
        </form>
    </div>
@endsection
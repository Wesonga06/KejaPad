@extends('layouts.app')

@section('page-title', 'Submit Maintenance Request')


@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Submit Maintenance Request</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Describe the issue and we'll get it sorted.
            </p>
        </div>
        <a href="{{ route('tenant.maintenance.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:650px;">
        <form method="POST" action="{{ route('tenant.maintenance.store') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Title</label>
                <input type="text" name="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}"
                       placeholder="e.g. Leaking pipe in bathroom">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category"
                            class="form-select @error('category') is-invalid @enderror">
                        <option value="">-- Select Category --</option>
                        <option value="plumbing"   {{ old('category') === 'plumbing'   ? 'selected' : '' }}>Plumbing</option>
                        <option value="electrical" {{ old('category') === 'electrical' ? 'selected' : '' }}>Electrical</option>
                        <option value="security"   {{ old('category') === 'security'   ? 'selected' : '' }}>Security</option>
                        <option value="cleaning"   {{ old('category') === 'cleaning'   ? 'selected' : '' }}>Cleaning</option>
                        <option value="general"    {{ old('category') === 'general'    ? 'selected' : '' }}>General Maintenance</option>
                    </select>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Priority</label>
                    <select name="priority"
                            class="form-select @error('priority') is-invalid @enderror">
                        <option value="low"    {{ old('priority') === 'low'    ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority') === 'medium' ? 'selected' : '' }} selected>Medium</option>
                        <option value="high"   {{ old('priority') === 'high'   ? 'selected' : '' }}>High</option>
                    </select>
                    @error('priority')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="Describe the issue in detail...">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Photo <span class="text-muted fw-normal">(optional)</span>
                </label>
                <input type="file" name="photo"
                       class="form-control @error('photo') is-invalid @enderror"
                       accept="image/*">
                @error('photo')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send me-2"></i> Submit Request
            </button>
        </form>
    </div>
@endsection
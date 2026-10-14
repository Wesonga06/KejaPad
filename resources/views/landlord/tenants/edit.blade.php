@extends('layouts.app')

@section('page-title', 'Edit Tenant')


@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Edit Tenant</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Update details for {{ $tenant->full_name }}.</p>
        </div>
        <a href="{{ route('landlord.tenants.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:700px;">
        <form method="POST" action="{{ route('landlord.tenants.update', $tenant) }}">
            @csrf @method('PUT')

            <h6 class="fw-bold mb-3 pb-2 border-bottom">Personal Information</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" name="full_name"
                           class="form-control @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name', $tenant->full_name) }}">
                    @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $tenant->phone) }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $tenant->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">National ID</label>
                    <input type="text" name="national_id"
                           class="form-control @error('national_id') is-invalid @enderror"
                           value="{{ old('national_id', $tenant->national_id) }}">
                    @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <h6 class="fw-bold mb-3 pb-2 border-bottom mt-4">Emergency Contact</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Name</label>
                    <input type="text" name="emergency_contact_name"
                           class="form-control @error('emergency_contact_name') is-invalid @enderror"
                           value="{{ old('emergency_contact_name', $tenant->emergency_contact_name) }}">
                    @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Phone</label>
                    <input type="text" name="emergency_contact_phone"
                           class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                           value="{{ old('emergency_contact_phone', $tenant->emergency_contact_phone) }}">
                    @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <h6 class="fw-bold mb-3 pb-2 border-bottom mt-4">Tenancy Dates</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Move-in Date</label>
                    <input type="date" name="move_in_date"
                           class="form-control @error('move_in_date') is-invalid @enderror"
                           value="{{ old('move_in_date', $tenant->move_in_date->format('Y-m-d')) }}">
                    @error('move_in_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Move-out Date <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="date" name="move_out_date"
                           class="form-control @error('move_out_date') is-invalid @enderror"
                           value="{{ old('move_out_date', $tenant->move_out_date?->format('Y-m-d')) }}">
                    @error('move_out_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <!-- Current Unit Info (read-only) -->
            <div class="alert alert-light border mt-2">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    Currently in <strong>{{ $tenant->unit->unit_number }}</strong>
                    at <strong>{{ $tenant->property->name }}</strong>.
                    To change units, deactivate this tenant and add them again.
                </small>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3">
                <i class="bi bi-save me-2"></i> Save Changes
            </button>
        </form>
    </div>
@endsection
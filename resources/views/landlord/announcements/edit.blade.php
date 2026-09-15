@extends('layouts.app')

@section('page-title', 'Edit Announcement')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
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
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link active">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Edit Announcement</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Update the announcement details.
            </p>
        </div>
        <a href="{{ route('landlord.announcements.index') }}"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:650px;">
        <form method="POST"
              action="{{ route('landlord.announcements.update', $announcement) }}">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Title</label>
                <input type="text" name="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $announcement->title) }}">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category"
                            class="form-select @error('category') is-invalid @enderror">
                        <option value="general"     {{ $announcement->category === 'general'     ? 'selected' : '' }}>General</option>
                        <option value="water"       {{ $announcement->category === 'water'       ? 'selected' : '' }}>Water</option>
                        <option value="electricity" {{ $announcement->category === 'electricity' ? 'selected' : '' }}>Electricity</option>
                        <option value="security"    {{ $announcement->category === 'security'    ? 'selected' : '' }}>Security</option>
                        <option value="maintenance" {{ $announcement->category === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="other"       {{ $announcement->category === 'other'       ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Audience</label>
                    <select name="audience"
                            class="form-select @error('audience') is-invalid @enderror">
                        <option value="all"        {{ $announcement->audience === 'all'        ? 'selected' : '' }}>Everyone</option>
                        <option value="tenants"    {{ $announcement->audience === 'tenants'    ? 'selected' : '' }}>Tenants Only</option>
                        <option value="caretakers" {{ $announcement->audience === 'caretakers' ? 'selected' : '' }}>Caretakers Only</option>
                    </select>
                    @error('audience')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">
                    Property
                    <span class="text-muted fw-normal">(leave blank for all properties)</span>
                </label>
                <select name="property_id"
                        class="form-select @error('property_id') is-invalid @enderror">
                    <option value="">-- All Properties --</option>
                    @foreach($properties as $property)
                        <option value="{{ $property->id }}"
                            {{ $announcement->property_id == $property->id ? 'selected' : '' }}>
                            {{ $property->name }}
                        </option>
                    @endforeach
                </select>
                @error('property_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Message</label>
                <textarea name="body" rows="5"
                          class="form-control @error('body') is-invalid @enderror">{{ old('body', $announcement->body) }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save me-2"></i> Save Changes
            </button>
        </form>
    </div>
@endsection
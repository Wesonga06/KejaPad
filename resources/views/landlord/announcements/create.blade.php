@extends('layouts.app')

@section('page-title', 'Post Announcement')

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
            <h5 class="fw-bold mb-1">Post New Announcement</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Send a notice to your tenants or caretakers.
            </p>
        </div>
        <a href="{{ route('landlord.announcements.index') }}"
           class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:650px;">
        <form method="POST" action="{{ route('landlord.announcements.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Title</label>
                <input type="text" name="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}"
                       placeholder="e.g. Water Interruption Notice">
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category"
                            class="form-select @error('category') is-invalid @enderror">
                        <option value="general"     {{ old('category') === 'general'     ? 'selected' : '' }}>General</option>
                        <option value="water"       {{ old('category') === 'water'       ? 'selected' : '' }}>Water</option>
                        <option value="electricity" {{ old('category') === 'electricity' ? 'selected' : '' }}>Electricity</option>
                        <option value="security"    {{ old('category') === 'security'    ? 'selected' : '' }}>Security</option>
                        <option value="maintenance" {{ old('category') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="other"       {{ old('category') === 'other'       ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('category')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Audience</label>
                    <select name="audience"
                            class="form-select @error('audience') is-invalid @enderror">
                        <option value="all"        {{ old('audience') === 'all'        ? 'selected' : '' }}>Everyone</option>
                        <option value="tenants"    {{ old('audience') === 'tenants'    ? 'selected' : '' }}>Tenants Only</option>
                        <option value="caretakers" {{ old('audience') === 'caretakers' ? 'selected' : '' }}>Caretakers Only</option>
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
                            {{ old('property_id') == $property->id ? 'selected' : '' }}>
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
                          class="form-control @error('body') is-invalid @enderror"
                          placeholder="Write your announcement here...">{{ old('body') }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-megaphone me-2"></i> Post Announcement
            </button>
        </form>
    </div>
@endsection
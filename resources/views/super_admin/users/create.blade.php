@extends('layouts.app')

@section('page-title', 'Add New User')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('super_admin.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('super_admin.users.index') }}" class="nav-link active">
        <i class="bi bi-people"></i> Users
    </a>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('super_admin.settings.index') }}" class="nav-link">
        <i class="bi bi-gear"></i> System Settings
    </a>

    <div class="nav-section-title">Reports</div>
    <a href="{{ route('super_admin.reports.index') }}" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Add New User</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Create a new user and assign a role.</p>
        </div>
        <a href="{{ route('super_admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:600px;">
        <form method="POST" action="{{ route('super_admin.users.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-500">Full Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" placeholder="Enter full name">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-500">Email Address</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email') }}" placeholder="Enter email address">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-500">Role</label>
                <select name="role" class="form-select @error('role') is-invalid @enderror">
                    <option value="">-- Select Role --</option>
                    <option value="super_admin" {{ old('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="landlord" {{ old('role') === 'landlord' ? 'selected' : '' }}>Landlord</option>
                    <option value="caretaker" {{ old('role') === 'caretaker' ? 'selected' : '' }}>Caretaker</option>
                    <option value="tenant" {{ old('role') === 'tenant' ? 'selected' : '' }}>Tenant</option>
                </select>
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-500">Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       placeholder="Min 8 characters">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-500">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control"
                       placeholder="Repeat password">
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-person-plus me-2"></i> Create User
            </button>
        </form>
    </div>
@endsection
@extends('layouts.app')

@section('page-title', 'My Profile')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('tenant.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('tenant.announcements.index') }}" class="nav-link active">
        <i class="bi bi-megaphone"></i> Announcements
    </a>

    <div class="nav-section-title">My Account</div>
    <a href="{{ route('tenant.payments') }}" class="nav-link">
        <i class="bi bi-receipt"></i> Payment History
    </a>
    <a href="{{ route('tenant.maintenance.index') }}" class="nav-link">
    <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('tenant.profile') }}" class="nav-link active">
        <i class="bi bi-person-circle"></i> My Profile
    </a>
@endsection

@section('content')
    <div class="mb-4">
        <h5 class="fw-bold mb-1">My Profile</h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Update your account details and password.
        </p>
    </div>

    <div class="row g-4">
        <!-- Tenant Info (read-only) -->
        @if($tenant)
        <div class="col-md-4">
            <div class="stat-card">
                <div class="text-center mb-3">
                    <div class="mx-auto mb-3"
                         style="width:65px; height:65px;
                                background:linear-gradient(135deg,#4cc9f0,#0f3460);
                                border-radius:50%; display:flex;
                                align-items:center; justify-content:center;
                                color:white; font-weight:700; font-size:1.4rem;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <h6 class="fw-bold mb-0">{{ $tenant->full_name }}</h6>
                    <small class="text-muted">{{ $tenant->phone }}</small>
                </div>

                <div class="border-top pt-3">
                    <div class="mb-2">
                        <small class="text-muted d-block">Property</small>
                        <span class="fw-semibold">{{ $tenant->property->name }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Unit</small>
                        <span class="fw-semibold">{{ $tenant->unit->unit_number }}</span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Monthly Rent</small>
                        <span class="fw-semibold">
                            KES {{ number_format($tenant->unit->rent_amount, 2) }}
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Move-in Date</small>
                        <span class="fw-semibold">
                            {{ $tenant->move_in_date->format('d M Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Update Form -->
        <div class="col-md-8">
            <div class="stat-card">
                <form method="POST" action="{{ route('tenant.profile.update') }}">
                    @csrf @method('PUT')

                    <h6 class="fw-bold mb-3 pb-2 border-bottom">
                        Account Details
                    </h6>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', auth()->user()->name) }}">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', auth()->user()->email) }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <h6 class="fw-bold mb-3 pb-2 border-bottom">
                        Change Password
                        <small class="text-muted fw-normal">
                            (leave blank to keep current)
                        </small>
                    </h6>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               placeholder="Enter current password">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password"
                               class="form-control @error('new_password') is-invalid @enderror"
                               placeholder="Min 8 characters">
                        @error('new_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            Confirm New Password
                        </label>
                        <input type="password" name="new_password_confirmation"
                               class="form-control"
                               placeholder="Repeat new password">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-2"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
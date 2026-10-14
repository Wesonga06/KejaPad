@extends('layouts.app')

@section('page-title', 'Settings')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Settings</h1>
            <p class="kp-page-subtitle">Manage your profile information and account security.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <!-- Profile Information -->
            <div class="kp-card mb-4">
                <div class="kp-card-header">
                    <div>
                        <h2 class="kp-card-title">Profile Information</h2>
                        <p class="kp-card-subtitle">Update your name and email address.</p>
                    </div>
                </div>

                @if (session('status') === 'profile-updated')
                    <div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i>Profile updated.</div>
                @endif

                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PATCH')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Name</label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="bi bi-save me-2"></i> Save Changes
                    </button>
                </form>
            </div>

            <!-- Update Password -->
            <div class="kp-card mb-4">
                <div class="kp-card-header">
                    <div>
                        <h2 class="kp-card-title">Update Password</h2>
                        <p class="kp-card-subtitle">Use a long, random password to stay secure.</p>
                    </div>
                </div>

                @if (session('status') === 'password-updated')
                    <div class="alert alert-success mb-3"><i class="bi bi-check-circle me-2"></i>Password updated.</div>
                @endif

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password"
                                   class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif"
                                   autocomplete="current-password">
                            @if($errors->updatePassword->has('current_password'))
                                <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="password">New Password</label>
                            <input type="password" name="password" id="password"
                                   class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif"
                                   autocomplete="new-password">
                            @if($errors->updatePassword->has('password'))
                                <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="password_confirmation">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" autocomplete="new-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">
                        <i class="bi bi-shield-lock me-2"></i> Update Password
                    </button>
                </form>
            </div>

            <!-- Delete Account -->
            <div class="kp-card" style="border-color: rgba(220,69,69,0.35);">
                <div class="kp-card-header">
                    <div>
                        <h2 class="kp-card-title" style="color:var(--kp-danger);">Delete Account</h2>
                        <p class="kp-card-subtitle">
                            Once your account is deleted, all of its resources and data will be permanently removed.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('profile.destroy') }}"
                      onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.')">
                    @csrf
                    @method('DELETE')

                    <div style="max-width: 360px;">
                        <label class="form-label" for="delete_password">Confirm your password</label>
                        <input type="password" name="password" id="delete_password"
                               class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                               placeholder="Enter password to confirm">
                        @if($errors->userDeletion->has('password'))
                            <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-outline-danger mt-3">
                        <i class="bi bi-trash me-2"></i> Delete Account
                    </button>
                </form>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="kp-card">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Your Account</h2>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="kp-avatar" style="width:52px; height:52px; font-size:1.1rem;">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="fw-bold" style="color:var(--kp-deep);">{{ $user->name }}</div>
                        <div class="text-muted" style="font-size:0.83rem;">{{ $user->email }}</div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2" style="border-top:1px solid var(--kp-border);">
                    <span class="text-muted" style="font-size:0.85rem;">Role</span>
                    <span class="badge badge-{{ $user->role }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between py-2" style="border-top:1px solid var(--kp-border);">
                    <span class="text-muted" style="font-size:0.85rem;">Member since</span>
                    <span class="fw-semibold" style="font-size:0.85rem;">{{ $user->created_at?->format('d M Y') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

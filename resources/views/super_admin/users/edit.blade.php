@extends('layouts.app')

@section('page-title', 'Edit User')

@section('content')
    @php $isSelf = auth()->id() === $user->id; @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Edit User</h1>
            <p class="kp-page-subtitle">Update account details, role and access.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.users.show', $user) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    @if($isSelf)
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            You are editing your own account. For safety, your role and access state cannot be changed here.
        </div>
    @endif

    <div class="kp-card" style="max-width:680px;">
        <form method="POST" action="{{ route('super_admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold">Full Name</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $user->name) }}" placeholder="Enter full name">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Email Address</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $user->email) }}" placeholder="Enter email address">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Phone Number</label>
                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                       value="{{ old('phone', $user->phone) }}" placeholder="e.g. 0712 345 678">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Role</label>
                <select name="role" class="form-select @error('role') is-invalid @enderror" @disabled($isSelf)>
                    @foreach(['super_admin' => 'Super Admin', 'landlord' => 'Landlord', 'caretaker' => 'Caretaker', 'tenant' => 'Tenant'] as $value => $label)
                        <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if($isSelf)
                    <input type="hidden" name="role" value="{{ $user->role }}">
                @endif
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active"
                           class="form-check-input @error('is_active') is-invalid @enderror"
                           {{ old('is_active', $user->is_active) ? 'checked' : '' }} @disabled($isSelf)>
                    <label class="form-check-label" for="is_active">Account active</label>
                    @error('is_active') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                @if($isSelf)
                    <input type="hidden" name="is_active" value="1">
                @endif
            </div>

            <hr class="my-4">

            <p class="text-muted" style="font-size:0.85rem;">Leave the password fields empty to keep the current password.</p>

            <div class="mb-3">
                <label class="form-label fw-semibold">New Password</label>
                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                       placeholder="Min 8 characters">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Confirm New Password</label>
                <input type="password" name="password_confirmation" class="form-control"
                       placeholder="Repeat new password">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-2"></i> Save Changes
                </button>
                <a href="{{ route('super_admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
@endsection

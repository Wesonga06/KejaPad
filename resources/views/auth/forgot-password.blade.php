@extends('layouts.auth')

@section('title', 'Forgot Password — KejaPad')
@section('heading', 'Forgot your password?')
@section('subtitle', 'No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.')

@section('content')
    @if (session('status'))
        <div class="alert alert-success d-flex align-items-center gap-2" style="font-size:0.85rem;">
            <i class="bi bi-check-circle"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">Email Address</label>
            <input id="email" type="email" name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   placeholder="your@email.com"
                   required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-auth">
            <i class="bi bi-envelope me-2"></i> Email Password Reset Link
        </button>
    </form>

    <div class="auth-links">
        Remembered your password?
        <a href="{{ route('login') }}">Back to sign in</a>
    </div>
@endsection

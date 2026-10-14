@extends('layouts.auth')

@section('title', 'Verify Email — KejaPad')
@section('heading', 'Verify your email')
@section('subtitle', 'Thanks for signing up! Before getting started, please verify your email address by clicking the link we just emailed to you.')

@section('content')
    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success d-flex align-items-center gap-2" style="font-size:0.85rem;">
            <i class="bi bi-check-circle"></i>
            <span>A new verification link has been sent to the email address you provided during registration.</span>
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between gap-3 mt-2">
        <form method="POST" action="{{ route('verification.send') }}" class="flex-fill">
            @csrf
            <button type="submit" class="btn-auth">
                <i class="bi bi-envelope-arrow-up me-2"></i> Resend Verification Email
            </button>
        </form>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="text-center mt-3">
        @csrf
        <button type="submit" class="btn btn-link p-0" style="font-size:0.85rem; color:var(--kp-muted); text-decoration:none;">
            Log out
        </button>
    </form>
@endsection

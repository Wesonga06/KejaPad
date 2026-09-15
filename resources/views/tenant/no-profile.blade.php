@extends('layouts.app')

@section('page-title', 'Account Setup Pending')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('tenant.dashboard') }}" class="nav-link active">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
@endsection

@section('content')
    <div class="stat-card text-center py-5">
        <i class="bi bi-person-exclamation"
           style="font-size:3rem; color:#fd7e14;"></i>
        <h5 class="mt-3 fw-bold">Profile Not Set Up Yet</h5>
        <p class="text-muted">
            Your tenant profile has not been linked to your account yet.<br>
            Please contact your landlord to have your profile set up.
        </p>
        <p class="text-muted" style="font-size:0.85rem;">
            Logged in as: <strong>{{ auth()->user()->email }}</strong>
        </p>
    </div>
@endsection
@extends('layouts.app')

@section('page-title', 'Account Setup Pending')


@section('content')
    <div class="stat-card text-center py-5">
        <i class="bi bi-person-exclamation"
           style="font-size:3rem; color:#E99B20;"></i>
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
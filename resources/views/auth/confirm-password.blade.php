@extends('layouts.auth')

@section('title', 'Confirm Password — KejaPad')
@section('heading', 'Confirm password')
@section('subtitle', 'This is a secure area of the application. Please confirm your password before continuing.')

@section('content')
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Enter your password"
                   required autofocus autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn-auth">
            <i class="bi bi-check2-circle me-2"></i> Confirm
        </button>
    </form>
@endsection

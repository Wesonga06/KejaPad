@extends('layouts.app')

@section('page-title', 'Account Approval')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            @php
                $isPending = $user->isPendingApproval();
                $isRejected = $user->isRejected();
                $isSuspended = $user->isSuspended();

                $icon = $isRejected ? 'bi-x-octagon' : ($isSuspended ? 'bi-pause-circle' : 'bi-hourglass-split');
                $tone = $isRejected ? 'var(--kp-danger)' : ($isSuspended ? 'var(--kp-muted)' : '#b7791f');
            @endphp

            <div class="kp-card">
                <div class="card-body text-center py-5">
                    <div class="kp-kpi-icon {{ $isRejected ? 'red' : ($isSuspended ? 'purple' : 'amber') }} mx-auto mb-3"
                         style="width:64px; height:64px; font-size:1.6rem;">
                        <i class="bi {{ $icon }}"></i>
                    </div>

                    @if($isPending)
                        <h1 class="kp-page-title mb-2">Your application is under review</h1>
                        <p class="kp-page-subtitle mb-4">
                            Thanks for registering, {{ $user->name }}. A Super Admin needs to approve your
                            landlord account before you can access landlord features.
                        </p>
                    @elseif($isRejected)
                        <h1 class="kp-page-title mb-2">Your application was not approved</h1>
                        <p class="kp-page-subtitle mb-4">
                            A Super Admin reviewed your application and it was not approved.
                        </p>
                    @elseif($isSuspended)
                        <h1 class="kp-page-title mb-2">Your account has been suspended</h1>
                        <p class="kp-page-subtitle mb-4">
                            Your landlord account is currently suspended and access is restricted.
                        </p>
                    @endif

                    @if($user->approval_reason)
                        <div class="alert" style="background: rgba(233,155,32,0.1); border:1px solid rgba(233,155,32,0.3); color: var(--kp-text); text-align:left;">
                            <strong>Reason:</strong> {{ $user->approval_reason }}
                        </div>
                    @endif

                    <div class="d-flex gap-2 justify-content-center mt-4">
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary">
                            <i class="bi bi-person me-2"></i> My profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-secondary">
                                <i class="bi bi-box-arrow-right me-2"></i> Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <p class="text-center text-muted mt-3" style="font-size:0.82rem;">
                Need help? Contact KejaPad support and quote your account email ({{ $user->email }}).
            </p>
        </div>
    </div>
@endsection

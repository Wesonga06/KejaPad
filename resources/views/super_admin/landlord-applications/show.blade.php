@extends('layouts.app')

@section('page-title', 'Landlord Application')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">{{ $landlord->name }}</h1>
            <p class="kp-page-subtitle">{{ $landlord->email }}</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.landlord-applications.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i> Back to applications
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Application details</h2>
                    @php
                        $badge = match($landlord->status) {
                            'approved' => 'kp-badge-success',
                            'rejected' => 'kp-badge-danger',
                            'suspended' => 'kp-badge-muted',
                            default => 'kp-badge-warning',
                        };
                    @endphp
                    <span class="kp-badge {{ $badge }}">{{ $landlord->statusLabel() }}</span>
                </div>
                <dl class="row mb-0" style="font-size:0.9rem;">
                    <dt class="col-5 text-muted">Name</dt>
                    <dd class="col-7 fw-semibold">{{ $landlord->name }}</dd>
                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7">{{ $landlord->email }}</dd>
                    <dt class="col-5 text-muted">Email verified</dt>
                    <dd class="col-7">{{ $landlord->email_verified_at ? $landlord->email_verified_at->format('d M Y') : 'Not verified' }}</dd>
                    <dt class="col-5 text-muted">Registered</dt>
                    <dd class="col-7">{{ $landlord->created_at?->format('d M Y H:i') }}</dd>
                    <dt class="col-5 text-muted">Approved</dt>
                    <dd class="col-7">{{ $landlord->approved_at?->format('d M Y H:i') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Rejected</dt>
                    <dd class="col-7">{{ $landlord->rejected_at?->format('d M Y H:i') ?? '—' }}</dd>
                    <dt class="col-5 text-muted">Latest reason</dt>
                    <dd class="col-7">{{ $landlord->approval_reason ?? '—' }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="kp-card mb-4">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Decision</h2>
                </div>

                @if($landlord->status !== 'approved')
                    <form method="POST" action="{{ route('super_admin.landlord-applications.approve', $landlord) }}" class="mb-3">
                        @csrf
                        <button class="btn btn-success">
                            <i class="bi bi-check2-circle me-2"></i>
                            {{ $landlord->status === 'pending_approval' ? 'Approve application' : 'Reinstate & approve' }}
                        </button>
                    </form>
                @endif

                @if($landlord->status === 'approved')
                    <form method="POST" action="{{ route('super_admin.landlord-applications.suspend', $landlord) }}" class="mb-3">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Suspension reason</label>
                            <textarea name="reason" rows="2" class="form-control @error('reason') is-invalid @enderror"
                                      placeholder="Explain why this landlord is being suspended">{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-outline-secondary">
                            <i class="bi bi-pause-circle me-2"></i> Suspend account
                        </button>
                    </form>
                @endif

                @if(in_array($landlord->status, ['pending_approval', 'approved'], true))
                    <form method="POST" action="{{ route('super_admin.landlord-applications.reject', $landlord) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Rejection reason <span class="text-danger">*</span></label>
                            <textarea name="reason" rows="2" class="form-control @error('reason') is-invalid @enderror"
                                      placeholder="Explain why this application is rejected" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-outline-danger">
                            <i class="bi bi-x-circle me-2"></i> Reject application
                        </button>
                    </form>
                @endif

                @if($landlord->status === 'suspended')
                    <form method="POST" action="{{ route('super_admin.landlord-applications.reinstate', $landlord) }}">
                        @csrf
                        <button class="btn btn-success">
                            <i class="bi bi-arrow-counterclockwise me-2"></i> Reinstate account
                        </button>
                    </form>
                @endif
            </div>

            <div class="kp-card">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Review history</h2>
                </div>
                @if($landlord->landlordApprovalActions->isEmpty())
                    <div class="kp-empty">
                        <i class="bi bi-clock-history"></i>
                        <p class="mb-0">No review actions recorded yet.</p>
                    </div>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach($landlord->landlordApprovalActions as $action)
                            <li class="d-flex gap-3 py-2 border-bottom">
                                <div class="kp-kpi-icon purple"><i class="bi bi-dot"></i></div>
                                <div>
                                    <div class="fw-semibold">{{ $action->actionLabel() }}</div>
                                    <div class="text-muted" style="font-size:0.82rem;">
                                        {{ $action->performedBy?->name ?? 'Applicant' }}
                                        &middot; {{ $action->created_at?->format('d M Y H:i') }}
                                    </div>
                                    @if($action->reason)
                                        <div style="font-size:0.85rem;">Reason: {{ $action->reason }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection

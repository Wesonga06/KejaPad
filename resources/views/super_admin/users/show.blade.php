@extends('layouts.app')

@section('page-title', 'User Details')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">{{ $user->name }}</h1>
            <p class="kp-page-subtitle">Account overview and activity trail.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
            <a href="{{ route('super_admin.users.edit', $user) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i> Edit
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Account</h2>
                </div>
                <div class="mb-3 d-flex gap-2 flex-wrap">
                    @php
                        $roleBadge = match($user->role) {
                            'super_admin' => 'kp-badge-purple',
                            'landlord' => 'kp-badge-gold',
                            'caretaker' => 'kp-badge-success',
                            default => 'kp-badge-muted',
                        };
                    @endphp
                    <span class="kp-badge {{ $roleBadge }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                    <span class="kp-badge {{ $user->statusBadgeClass() }}">{{ $user->statusLabel() }}</span>
                    <span class="kp-badge {{ $user->activeBadgeClass() }}">{{ $user->activeLabel() }}</span>
                </div>
                <dl class="row mb-0" style="font-size:0.9rem;">
                    <dt class="col-5 text-muted fw-normal">Email</dt>
                    <dd class="col-7">{{ $user->email }}</dd>
                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-7">{{ $user->phone ?: '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">User ID</dt>
                    <dd class="col-7">#{{ $user->id }}</dd>
                    <dt class="col-5 text-muted fw-normal">Registered</dt>
                    <dd class="col-7">{{ $user->created_at?->format('d M Y, H:i') }}</dd>
                    @if($user->approved_at)
                        <dt class="col-5 text-muted fw-normal">Approved</dt>
                        <dd class="col-7">{{ $user->approved_at?->format('d M Y') }}</dd>
                    @endif
                    @if($user->rejected_at)
                        <dt class="col-5 text-muted fw-normal">Rejected</dt>
                        <dd class="col-7">{{ $user->rejected_at?->format('d M Y') }}</dd>
                    @endif
                </dl>
                @if($user->approval_reason)
                    <div class="mt-3 p-3 rounded" style="background:var(--kp-bg); font-size:0.85rem;">
                        <div class="fw-semibold mb-1">Approval note</div>
                        {{ $user->approval_reason }}
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Role profile</h2>
                </div>

                @if($user->role === 'landlord')
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="kp-kpi-value">{{ number_format($related['properties']) }}</div>
                            <p class="kp-kpi-label">Properties</p>
                        </div>
                        @if($related['landlord_approval'])
                            <div class="col-12">
                                <div class="p-3 rounded" style="background:var(--kp-bg); font-size:0.85rem;">
                                    <span class="fw-semibold">Last review action:</span>
                                    {{ $related['landlord_approval']->action }}
                                    <span class="text-muted">· {{ $related['landlord_approval']->created_at?->format('d M Y') }}</span>
                                    @if($related['landlord_approval']->reason)
                                        <div class="text-muted mt-1">{{ $related['landlord_approval']->reason }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif($user->role === 'caretaker')
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="kp-kpi-value">{{ number_format($related['caretaker_properties']) }}</div>
                            <p class="kp-kpi-label">Assigned properties</p>
                        </div>
                    </div>
                @elseif($user->role === 'tenant' && $related['tenant_profile'])
                    @php $tenant = $related['tenant_profile']; @endphp
                    <dl class="row mb-0" style="font-size:0.9rem;">
                        <dt class="col-5 text-muted fw-normal">Tenant name</dt>
                        <dd class="col-7">{{ $tenant->full_name }}</dd>
                        <dt class="col-5 text-muted fw-normal">Property</dt>
                        <dd class="col-7">{{ $tenant->property?->name ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Unit</dt>
                        <dd class="col-7">{{ $tenant->unit?->unit_number ?? '—' }}</dd>
                        <dt class="col-5 text-muted fw-normal">Move-in date</dt>
                        <dd class="col-7">{{ $tenant->move_in_date?->format('d M Y') }}</dd>
                    </dl>
                @else
                    <div class="kp-empty py-4">
                        <i class="bi bi-person-badge"></i>
                        <p class="mb-0">No role-specific profile to display.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12">
            <div class="kp-card">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Activity trail</h2>
                    <p class="kp-card-subtitle">Administrative actions performed by or about this account.</p>
                </div>
                @if($activity->isEmpty())
                    <div class="kp-empty">
                        <i class="bi bi-clock-history"></i>
                        <p class="mb-0">No recorded activity.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>By</th>
                                    <th>IP</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activity as $log)
                                    <tr>
                                        <td><span class="kp-badge {{ $log->badgeClass() }}">{{ $log->actionLabel() }}</span></td>
                                        <td>{{ $log->description }}</td>
                                        <td>{{ $log->user?->name ?? 'System' }}</td>
                                        <td class="text-muted" style="font-size:0.8rem;">{{ $log->ip_address }}</td>
                                        <td class="text-muted" style="font-size:0.85rem;">{{ $log->created_at?->format('d M Y, H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $activity->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

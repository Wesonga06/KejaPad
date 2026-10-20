@extends('layouts.app')

@section('page-title', 'Caretaker Invitations')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Caretaker Invitations</h1>
            <p class="kp-page-subtitle">Invite caretakers to manage a specific property. Only a valid invitation can create a caretaker account.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.caretaker-invitations.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i> New Invitation
            </a>
        </div>
    </div>

    @if($newInvitation && $newInvitation->isRedeemable())
        <div class="kp-card mb-4" role="status" aria-labelledby="new-invitation-title" style="border-left: 4px solid var(--kp-royal);">
            <div class="kp-card-header">
                <h2 class="kp-card-title" id="new-invitation-title">Invitation ready to share</h2>
            </div>
            <p class="mb-2" style="font-size:0.88rem;">
                Property: <strong>{{ $newInvitation->property?->name }}</strong>
                <span class="d-block">Expires {{ optional($newInvitation->expires_at)->format('d M Y H:i') }}.</span>
            </p>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <code class="invitation-code" id="newInvitationCode">{{ $newInvitation->code }}</code>
                <button class="btn btn-outline-primary" type="button" data-copy-code="{{ $newInvitation->code }}" aria-label="Copy invitation code">
                    <i class="bi bi-clipboard me-1"></i> Copy Code
                </button>
            </div>
            <p class="mb-3">Share this code with your caretaker. They must enter it on the Kejapad registration page.</p>
            <div class="d-flex align-items-center gap-3">
                <span class="copy-feedback small" aria-live="polite"></span>
                <a href="{{ route('landlord.caretaker-invitations.index') }}" class="btn btn-sm btn-outline-secondary ms-auto">Close and return to invitations</a>
            </div>
        </div>
    @endif

    <div class="kp-card mb-4">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Invitations</h2>
        </div>

        @if($invitations->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-envelope-plus"></i>
                <p class="mb-0">No invitations sent yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Invited caretaker</th>
                            <th>Invitation code</th>
                            <th>Status</th>
                            <th>Expires</th>
                            <th>Redeemed by</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invitations as $invitation)
                            @php
                                $badge = match($invitation->displayStatus()) {
                                    'redeemed' => 'kp-badge-success',
                                    'revoked' => 'kp-badge-danger',
                                    'expired' => 'kp-badge-muted',
                                    default => 'kp-badge-warning',
                                };
                            @endphp
                            <tr>
                                <td class="fw-semibold">{{ $invitation->property?->name ?? '—' }}</td>
                                <td style="font-size:0.85rem;">
                                    {{ $invitation->name ?: '—' }}
                                    @if($invitation->email)
                                        <div class="text-muted">{{ $invitation->email }}</div>
                                    @else
                                        <div class="text-muted">Any email</div>
                                    @endif
                                </td>
                                <td>
                                    @if($invitation->displayStatus() === 'pending')
                                        <span class="font-monospace small">{{ $invitation->maskedCode() }}</span>
                                        <button class="btn btn-sm btn-link px-1" type="button" data-copy-code="{{ $invitation->code }}" aria-label="Copy invitation code for {{ $invitation->property?->name }}">Copy</button>
                                    @else
                                        <span class="font-monospace small text-muted">{{ $invitation->maskedCode() }}</span>
                                    @endif
                                </td>
                                <td><span class="kp-badge {{ $badge }}">{{ $invitation->statusLabel() }}</span></td>
                                <td class="text-muted" style="font-size:0.85rem;">
                                    {{ optional($invitation->expires_at)->format('d M Y H:i') ?? '—' }}
                                </td>
                                <td style="font-size:0.85rem;">
                                    {{ $invitation->redeemedBy?->name ?? '—' }}
                                </td>
                                <td class="text-end">
                                    @if($invitation->displayStatus() === 'pending')
                                        <form method="POST" action="{{ route('landlord.caretaker-invitations.revoke', $invitation) }}"
                                              onsubmit="return confirm('Revoke this invitation?');">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger">Revoke</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $invitations->links() }}</div>
        @endif
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Assigned caretakers</h2>
        </div>

        @php $hasAssignments = $properties->contains(fn ($p) => $p->caretakers->isNotEmpty()); @endphp

        @if(! $hasAssignments)
            <div class="kp-empty">
                <i class="bi bi-person-badge"></i>
                <p class="mb-0">No caretakers are assigned to your properties yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Caretaker</th>
                            <th>Email</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($properties as $property)
                            @foreach($property->caretakers as $caretaker)
                                <tr>
                                    <td class="fw-semibold">{{ $property->name }}</td>
                                    <td>{{ $caretaker->name }}</td>
                                    <td style="font-size:0.85rem;">{{ $caretaker->email }}</td>
                                    <td class="text-end">
                                        <form method="POST"
                                              action="{{ route('landlord.caretakers.revoke-access', [$caretaker, $property]) }}"
                                              onsubmit="return confirm('Revoke this caretaker\'s access to {{ $property->name }}? Historical records are preserved.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Revoke access</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <style>
        .invitation-code { display: inline-block; padding: .55rem .8rem; border-radius: .5rem; background: rgba(89,38,200,.07); color: var(--kp-royal); font-size: clamp(1.25rem, 4vw, 1.75rem); font-weight: 700; letter-spacing: .12em; }
    </style>
    <script>
        document.querySelectorAll('[data-copy-code]').forEach(function (button) {
            button.addEventListener('click', async function () {
                const code = button.dataset.copyCode;
                const feedback = button.closest('.kp-card')?.querySelector('.copy-feedback');
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(code);
                    } else {
                        const field = document.createElement('textarea');
                        field.value = code;
                        field.style.position = 'fixed';
                        field.style.opacity = '0';
                        document.body.appendChild(field);
                        field.select();
                        const copied = document.execCommand('copy');
                        field.remove();
                        if (!copied) throw new Error('Clipboard unavailable');
                    }
                    if (feedback) feedback.textContent = 'Invitation code copied.';
                    else button.textContent = 'Copied';
                } catch (error) {
                    if (feedback) feedback.textContent = 'Could not copy. Select the code and copy it manually.';
                    else button.textContent = 'Copy failed';
                }
            });
        });
    </script>
@endsection

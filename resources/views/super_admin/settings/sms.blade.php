@extends('layouts.app')

@section('page-title', 'SMS Settings')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">SMS Settings</h1>
            <p class="kp-page-subtitle">Choose an SMS gateway and manage automated tenant, landlord and caretaker messaging.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.sms.log') }}" class="btn btn-outline-primary">
                <i class="bi bi-journal-text me-2"></i> Message Log
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-chat-dots"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($stats['total']) }}</div>
                <p class="kp-kpi-label">Messages</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-send-check"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($stats['sent']) }}</div>
                <p class="kp-kpi-label">Sent</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon red"><i class="bi bi-exclamation-octagon"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($stats['failed']) }}</div>
                <p class="kp-kpi-label">Failed</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-hourglass-split"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($stats['queued']) }}</div>
                <p class="kp-kpi-label">Queued</p>
            </div>
        </div>
    </div>

    @if($provider === 'null')
        <div class="alert alert-warning d-flex align-items-start" role="alert">
            <i class="bi bi-exclamation-triangle me-2 mt-1"></i>
            <div>
                SMS is currently <strong>disabled</strong>. No messages are being sent until a provider is selected.
                Automated workflows will skip messaging while disabled.
            </div>
        </div>
    @elseif(! $credentials)
        <div class="alert alert-warning d-flex align-items-start" role="alert">
            <i class="bi bi-key me-2 mt-1"></i>
            <div>
                The selected provider is missing credentials in the environment. Messages will fail until
                the credentials are supplied. Secrets are never stored or displayed here.
            </div>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-7">
            <form method="POST" action="{{ route('super_admin.sms.update') }}" class="kp-card h-100">
                @csrf
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Gateway</h2>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Provider</label>
                        <select name="provider" class="form-select @error('provider') is-invalid @enderror">
                            @foreach(\App\Services\SmsService::PROVIDERS as $value => $label)
                                <option value="{{ $value }}" {{ $provider === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('provider') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold">Sender ID / From</label>
                        <input type="text" name="from" class="form-control @error('from') is-invalid @enderror"
                               value="{{ old('from', config('sms.from')) }}" placeholder="e.g. KEJAPAD" maxlength="20">
                        @error('from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="workflow_enabled" value="0">
                            <input type="checkbox" name="workflow_enabled" value="1" id="workflow_enabled"
                                   class="form-check-input" {{ old('workflow_enabled', config('sms.workflow_enabled')) ? 'checked' : '' }}>
                            <label class="form-check-label" for="workflow_enabled">
                                Send workflow messages (approvals, reminders, invitations)
                            </label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="bi bi-check2-circle me-2"></i> Save Gateway
                </button>
            </form>
        </div>

        <div class="col-12 col-lg-5">
            <form method="POST" action="{{ route('super_admin.sms.test') }}" class="kp-card h-100">
                @csrf
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Send a test message</h2>
                    <p class="kp-card-subtitle">Confirm your gateway configuration.</p>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Recipient</label>
                    <input type="text" name="recipient" class="form-control @error('recipient') is-invalid @enderror"
                           value="{{ old('recipient') }}" placeholder="e.g. 0712 345 678">
                    @error('recipient') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Message</label>
                    <textarea name="message" rows="3" class="form-control @error('message') is-invalid @enderror"
                              maxlength="500" placeholder="Test message from KejaPad">{{ old('message', 'This is a test message from KejaPad.') }}</textarea>
                    @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-send me-2"></i> Send Test
                </button>
            </form>
        </div>
    </div>

    <div class="kp-card mb-4" id="templates">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Message templates</h2>
            <p class="kp-card-subtitle">Use <code>@{{ variable }}</code> placeholders - they are replaced when the message is sent.</p>
        </div>
        <form method="POST" action="{{ route('super_admin.sms.templates.update') }}">
            @csrf
            <div class="row g-4">
                @foreach($templates as $template)
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-semibold">{{ $template->name }}</label>
                        <textarea name="templates[{{ $template->key }}]" rows="3"
                                  class="form-control @error('templates.'.$template->key) is-invalid @enderror"
                                  maxlength="500">{{ old('templates.'.$template->key, $template->body) }}</textarea>
                        @error('templates.'.$template->key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @php
                            preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $template->body, $matches);
                        @endphp
                        @if(! empty($matches[1]))
                            <div class="mt-2 d-flex flex-wrap gap-1">
                                @foreach(array_unique($matches[1]) as $variable)
                                    <span class="kp-badge kp-badge-muted" style="font-size:0.7rem;">{{ $variable }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-primary mt-3">
                <i class="bi bi-check2-circle me-2"></i> Save Templates
            </button>
        </form>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Recent messages</h2>
            <a href="{{ route('super_admin.sms.log') }}" class="btn btn-sm btn-outline-primary">View all</a>
        </div>
        @if($recent->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-chat-dots"></i>
                <p class="mb-0">No messages have been sent yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Recipient</th><th>Template</th><th>Status</th><th>When</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recent as $message)
                            <tr>
                                <td>{{ $message->recipient }}</td>
                                <td>{{ $message->template ?: '—' }}</td>
                                <td><span class="kp-badge {{ $message->statusBadgeClass() }}">{{ $message->statusLabel() }}</span></td>
                                <td class="text-muted" style="font-size:0.8rem;">{{ $message->created_at?->format('d M, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

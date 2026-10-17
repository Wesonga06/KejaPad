@extends('layouts.app')

@section('page-title', 'SMS Message Log')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">SMS Message Log</h1>
            <p class="kp-page-subtitle">Every message the platform has attempted to send.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.sms.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to SMS Settings
            </a>
        </div>
    </div>

    <div class="kp-card mb-4">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Filter</h2>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['' => 'All', 'sent' => 'Sent', 'failed' => 'Failed', 'queued' => 'Queued'] as $value => $label)
                    <a href="{{ route('super_admin.sms.log', array_filter(['status' => $value])) }}"
                       class="btn btn-sm {{ (string) $status === (string) $value ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <form method="GET" action="{{ route('super_admin.sms.log') }}" class="row g-3 align-items-end">
            @if($status !== '')<input type="hidden" name="status" value="{{ $status }}">@endif
            <div class="col-12 col-md-8">
                <label class="form-label fw-semibold">Search</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                       placeholder="Recipient, template or message text">
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Search</button>
            </div>
        </form>
    </div>

    <div class="kp-card">
        @if($messages->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-chat-dots"></i>
                <p class="mb-0">No messages found.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Template</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($messages as $message)
                            <tr>
                                <td class="fw-semibold">{{ $message->recipient }}</td>
                                <td>{{ $message->template ?: '—' }}</td>
                                <td class="text-muted" style="font-size:0.85rem;">{{ $message->provider }}</td>
                                <td><span class="kp-badge {{ $message->statusBadgeClass() }}">{{ $message->statusLabel() }}</span></td>
                                <td style="max-width:340px;">
                                    <div class="text-truncate" title="{{ $message->body }}">{{ $message->body }}</div>
                                    @if($message->error)
                                        <small class="text-danger d-block text-truncate" title="{{ $message->error }}">{{ $message->error }}</small>
                                    @endif
                                </td>
                                <td class="text-muted" style="font-size:0.8rem;">{{ $message->created_at?->format('d M Y, H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($messages->hasPages())
                <div class="mt-3">{{ $messages->links() }}</div>
            @endif
        @endif
    </div>
@endsection

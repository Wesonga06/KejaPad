@extends('layouts.app')

@section('page-title', 'Payment Settings')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Payment Settings</h1>
            <p class="kp-page-subtitle">Control accepted payment methods and M-Pesa verification behaviour.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.payments.transactions') }}" class="btn btn-outline-primary">
                <i class="bi bi-list-ul me-2"></i> Transactions
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">M-Pesa integration</h2>
                    <span class="kp-badge {{ $status['credentials'] ? 'kp-badge-success' : 'kp-badge-warning' }}">
                        {{ $status['credentials'] ? 'Credentials present' : 'Credentials missing' }}
                    </span>
                </div>
                <dl class="row mb-0" style="font-size:0.9rem;">
                    <dt class="col-5 text-muted fw-normal">Environment</dt>
                    <dd class="col-7">{{ ucfirst($status['env']) }}</dd>
                    <dt class="col-5 text-muted fw-normal">Shortcode</dt>
                    <dd class="col-7">{{ $status['shortcode'] ?: '—' }}</dd>
                    <dt class="col-5 text-muted fw-normal">Callback URL</dt>
                    <dd class="col-7 text-break">{{ $status['callback_url'] ?: 'Not set' }}</dd>
                </dl>
                <div class="alert alert-warning mt-3 mb-0" style="font-size:0.82rem;">
                    <i class="bi bi-key me-1"></i>
                    Consumer key, secret and passkey are read from the environment only and are never stored or
                    displayed here.
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Recorded payments</h2>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="kp-kpi-value">KES {{ number_format($summary['payments_total'], 0) }}</div>
                        <p class="kp-kpi-label">Total recorded value</p>
                    </div>
                    <div class="col-6">
                        <div class="kp-kpi-value">{{ number_format($summary['payments']) }}</div>
                        <p class="kp-kpi-label">Payment records</p>
                    </div>
                </div>
                <hr>
                <div class="d-flex flex-wrap gap-2">
                    <span class="kp-badge kp-badge-muted">M-Pesa: {{ number_format($summary['mpesa_total']) }}</span>
                    <span class="kp-badge kp-badge-warning">Pending: {{ number_format($summary['mpesa_pending']) }}</span>
                    <span class="kp-badge kp-badge-success">Confirmed: {{ number_format($summary['mpesa_confirmed']) }}</span>
                    <span class="kp-badge kp-badge-danger">Failed: {{ number_format($summary['mpesa_failed']) }}</span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('super_admin.payments.update') }}">
        @csrf

        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Accepted methods</h2>
                <p class="kp-card-subtitle">Disabled methods are rejected when recording rent, deposits and refunds.</p>
            </div>
            <div class="row g-3">
                @foreach($methods as $value => $label)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="form-check">
                            <input type="checkbox" name="methods[]" value="{{ $value }}" id="method_{{ $value }}"
                                   class="form-check-input" {{ in_array($value, $enabledMethods, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="method_{{ $value }}">{{ $label }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('methods') <div class="text-danger mt-2" style="font-size:0.85rem;">{{ $message }}</div> @enderror
            @error('methods.*') <div class="text-danger mt-2" style="font-size:0.85rem;">{{ $message }}</div> @enderror
        </div>

        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Verification &amp; security</h2>
            </div>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="require_verification" value="0">
                        <input type="checkbox" name="require_verification" value="1" id="require_verification"
                               class="form-check-input" {{ old('require_verification', $requireVerification) ? 'checked' : '' }}>
                        <label class="form-check-label" for="require_verification">
                            Require landlord confirmation before crediting an M-Pesa payment
                        </label>
                    </div>
                    <p class="text-muted mt-2 mb-0" style="font-size:0.8rem;">
                        When off, successful M-Pesa callbacks credit rent automatically. When on, the landlord must
                        confirm each payment first.
                    </p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-check form-switch">
                        <input type="hidden" name="callback_enforce_ip" value="0">
                        <input type="checkbox" name="callback_enforce_ip" value="1" id="callback_enforce_ip"
                               class="form-check-input" {{ old('callback_enforce_ip', $status['callback_enforce_ip']) ? 'checked' : '' }}>
                        <label class="form-check-label" for="callback_enforce_ip">
                            Restrict M-Pesa callbacks to allow-listed source IPs
                        </label>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">Allowed callback IPs</label>
                        <textarea name="callback_allowed_ips" rows="2" class="form-control @error('callback_allowed_ips') is-invalid @enderror"
                                  placeholder="197.248.0.0, 196.201.214.200">{{ old('callback_allowed_ips', implode(', ', $status['allowed_ips'])) }}</textarea>
                        @error('callback_allowed_ips') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <p class="text-muted mt-2 mb-0" style="font-size:0.8rem;">
                            Comma-separated. Leaving this blank while enforcement is on allows any source.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mb-4">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-2"></i> Save Payment Settings
            </button>
        </div>
    </form>

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Value by method</h2>
                </div>
                @if($summary['by_method']->isEmpty())
                    <div class="kp-empty py-4">
                        <i class="bi bi-cash-stack"></i>
                        <p class="mb-0">No payments recorded yet.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr><th>Method</th><th class="text-end">Count</th><th class="text-end">Value</th></tr>
                            </thead>
                            <tbody>
                                @foreach($summary['by_method'] as $method => $row)
                                    <tr>
                                        <td>{{ $methods[$method] ?? ucfirst($method) }}</td>
                                        <td class="text-end">{{ number_format($row->total) }}</td>
                                        <td class="text-end">KES {{ number_format((float) $row->value, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="kp-card h-100">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Recent M-Pesa activity</h2>
                </div>
                @if($recent->isEmpty())
                    <div class="kp-empty py-4">
                        <i class="bi bi-phone"></i>
                        <p class="mb-0">No M-Pesa transactions yet.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr><th>Tenant</th><th>Amount</th><th>Status</th><th>When</th></tr>
                            </thead>
                            <tbody>
                                @foreach($recent as $payment)
                                    <tr>
                                        <td>{{ $payment->tenant?->full_name ?? '—' }}</td>
                                        <td>KES {{ number_format((float) $payment->amount, 0) }}</td>
                                        <td>
                                            @php $badge = $payment->statusColor() === 'secondary' ? 'muted' : $payment->statusColor(); @endphp
                                            <span class="kp-badge kp-badge-{{ $badge }}">{{ ucfirst($payment->status) }}</span>
                                        </td>
                                        <td class="text-muted" style="font-size:0.8rem;">{{ $payment->created_at?->format('d M, H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

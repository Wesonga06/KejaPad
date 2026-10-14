@extends('layouts.app')

@section('page-title', 'Notices')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Notices</h1>
            <p class="kp-page-subtitle">Reminders, demand letters and formal notices issued to tenants.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.notices.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Record Notice
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $chips = [
                ['label' => 'Sent', 'key' => 'sent', 'color' => 'purple', 'icon' => 'bi-send'],
                ['label' => 'Delivered', 'key' => 'delivered', 'color' => 'green', 'icon' => 'bi-check2-circle'],
                ['label' => 'Acknowledged', 'key' => 'acknowledged', 'color' => 'gold', 'icon' => 'bi-hand-thumbs-up'],
                ['label' => 'Failed', 'key' => 'failed', 'color' => 'red', 'icon' => 'bi-x-circle'],
            ];
        @endphp
        @foreach($chips as $chip)
            <div class="col-6 col-md-3">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="kp-kpi-icon {{ $chip['color'] }}"><i class="bi {{ $chip['icon'] }}"></i></div>
                    <div>
                        <div class="kp-kpi-value" style="font-size:1.4rem; margin:0;">{{ $counts[$chip['key']] ?? 0 }}</div>
                        <div class="kp-kpi-label">{{ $chip['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="kp-card">
        <form method="GET" action="{{ route('landlord.notices.index') }}" class="d-flex flex-wrap gap-2 mb-4">
            <select name="type" class="form-select" style="max-width:220px;" onchange="this.form.submit()">
                <option value="">All types</option>
                @foreach(['payment_reminder' => 'Payment Reminder', 'follow_up_reminder' => 'Follow-up', 'escalation' => 'Escalation', 'formal_notice' => 'Formal Notice', 'demand_letter' => 'Demand Letter', 'other' => 'Other'] as $value => $label)
                    <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select" style="max-width:180px;" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['sent' => 'Sent', 'delivered' => 'Delivered', 'acknowledged' => 'Acknowledged', 'failed' => 'Failed', 'draft' => 'Draft'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if($type || $status)
                <a href="{{ route('landlord.notices.index') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </form>

        @if($notices->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-envelope-paper"></i>
                <p class="mb-3">No notices match your filters yet.</p>
                <a href="{{ route('landlord.notices.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i> Record Notice
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Tenant</th>
                            <th>Property / Unit</th>
                            <th>Type</th>
                            <th>Issued</th>
                            <th>Deadline</th>
                            <th>Delivery</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notices as $notice)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $notice->tenant->full_name }}</div>
                                    <div class="text-muted" style="font-size:0.78rem;">
                                        {{ $notice->recipient ?? ($notice->tenant->phone ?? '—') }}
                                    </div>
                                </td>
                                <td style="font-size:0.85rem;">
                                    {{ $notice->tenant->property->name }}
                                    <span class="text-muted">/ {{ $notice->tenant->unit->unit_number }}</span>
                                </td>
                                <td><span class="badge kp-badge-purple">{{ $notice->typeLabel() }}</span></td>
                                <td style="font-size:0.85rem;">{{ $notice->issued_at?->format('d M Y') ?? '—' }}</td>
                                <td style="font-size:0.85rem;">{{ $notice->deadline?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('landlord.notices.update', $notice) }}">
                                        @csrf @method('PATCH')
                                        <select name="delivery_status" class="form-select form-select-sm"
                                                style="min-width:140px;" onchange="this.form.submit()">
                                            @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'delivered' => 'Delivered', 'acknowledged' => 'Acknowledged', 'failed' => 'Failed'] as $value => $label)
                                                <option value="{{ $value }}" @selected($notice->delivery_status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('landlord.tenants.show', $notice->tenant) }}"
                                       class="btn btn-sm btn-outline-primary" title="View tenant account">
                                        <i class="bi bi-person-lines-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($notices->hasPages())
                <div class="mt-3">{{ $notices->links() }}</div>
            @endif
        @endif
    </div>
@endsection

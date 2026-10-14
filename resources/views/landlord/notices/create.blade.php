@extends('layouts.app')

@section('page-title', 'Record Notice')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Record Notice</h1>
            <p class="kp-page-subtitle">Issue a reminder, escalation or formal notice to a tenant.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.notices.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Notices
            </a>
        </div>
    </div>

    @if($tenants->isEmpty())
        <div class="kp-card kp-empty">
            <i class="bi bi-people"></i>
            <p class="mb-3">You have no active tenants to issue a notice to.</p>
            <a href="{{ route('landlord.tenants.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Add Tenant
            </a>
        </div>
    @else
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="kp-card">
                    <div class="kp-card-header">
                        <h2 class="kp-card-title">Notice details</h2>
                    </div>

                    <form method="POST"
                          action="{{ route('landlord.tenants.notices.store', $tenants->first()) }}"
                          id="noticeForm">
                        @csrf

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="tenant_id">Tenant</label>
                                <select name="tenant_id" id="tenant_id" class="form-select" required>
                                    @foreach($tenants as $tenant)
                                        <option value="{{ $tenant->id }}"
                                                data-url="{{ route('landlord.tenants.notices.store', $tenant) }}"
                                                data-recipient="{{ $tenant->email ?? $tenant->phone }}"
                                                @selected(old('tenant_id') == $tenant->id)>
                                            {{ $tenant->full_name }} — {{ $tenant->property->name }} / {{ $tenant->unit->unit_number }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-muted">The notice is stored against the selected tenant and appears on their account.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="type">Notice type</label>
                                <select name="type" id="type" class="form-select" required>
                                    @foreach(['payment_reminder' => 'Payment Reminder', 'follow_up_reminder' => 'Follow-up Reminder', 'escalation' => 'Escalation Notice', 'formal_notice' => 'Formal Notice', 'demand_letter' => 'Demand Letter', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type', 'payment_reminder') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="delivery_status">Delivery status</label>
                                <select name="delivery_status" id="delivery_status" class="form-select">
                                    @foreach(['sent' => 'Sent', 'draft' => 'Draft', 'delivered' => 'Delivered', 'acknowledged' => 'Acknowledged', 'failed' => 'Failed'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('delivery_status', 'sent') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="issued_at">Issued on</label>
                                <input type="date" name="issued_at" id="issued_at" class="form-control"
                                       value="{{ old('issued_at', now()->toDateString()) }}" required>
                                @error('issued_at')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="deadline">Deadline <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="date" name="deadline" id="deadline" class="form-control"
                                       value="{{ old('deadline') }}">
                                @error('deadline')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="recipient">Recipient <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="recipient" id="recipient" class="form-control"
                                       value="{{ old('recipient') }}" placeholder="Email or phone the notice was sent to">
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="notes">Notes <span class="text-muted fw-normal">(optional)</span></label>
                                <textarea name="notes" id="notes" rows="4" class="form-control"
                                          placeholder="Any additional details about this notice...">{{ old('notes') }}</textarea>
                                @error('notes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i> Save Notice
                            </button>
                            <a href="{{ route('landlord.notices.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="kp-card">
                    <div class="kp-card-header">
                        <h2 class="kp-card-title">Escalation stages</h2>
                    </div>
                    <p class="text-muted" style="font-size:0.85rem;">
                        KejaPad tracks notices along the standard arrears escalation path.
                    </p>
                    <ol class="mb-0 ps-3" style="font-size:0.87rem;">
                        <li class="mb-1">Payment Reminder</li>
                        <li class="mb-1">Follow-up Reminder</li>
                        <li class="mb-1">Escalation Notice</li>
                        <li>Formal Notice / Demand Letter</li>
                    </ol>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        (function () {
            const tenantSelect = document.getElementById('tenant_id');
            const form = document.getElementById('noticeForm');
            const recipient = document.getElementById('recipient');
            if (!tenantSelect || !form) return;

            const apply = () => {
                const opt = tenantSelect.options[tenantSelect.selectedIndex];
                form.action = opt.dataset.url;
                if (recipient && !recipient.value) {
                    recipient.placeholder = opt.dataset.recipient || 'Email or phone the notice was sent to';
                }
            };
            tenantSelect.addEventListener('change', apply);
            apply();
        })();
    </script>
    @endpush
@endsection

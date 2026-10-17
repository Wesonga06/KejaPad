@extends('layouts.app')

@section('page-title', 'Property Passport — ' . $unit->unit_number)

@section('content')
@php
    $isLandlord = $mode === 'landlord';
    $isCaretaker = $mode === 'caretaker';
    $isTenant = $mode === 'tenant';
    $isSuper = $mode === 'super_admin';
    $canManage = $isLandlord || $isCaretaker;

    $showUrl = function (string $t) use ($mode, $property, $unit) {
        return match ($mode) {
            'landlord' => route('landlord.passport.show', [$property, $unit, 'tab' => $t]),
            'caretaker' => route('caretaker.passport.show', [$unit, 'tab' => $t]),
            'tenant' => route('tenant.passport.show', ['tab' => $t]),
            'super_admin' => route('super_admin.passports.show', [$unit, 'tab' => $t]),
        };
    };
    $inspectionUrl = function ($inspection) use ($mode) {
        return match ($mode) {
            'landlord' => route('landlord.passport.inspections.show', $inspection),
            'caretaker' => route('caretaker.passport.inspections.show', $inspection),
            default => null,
        };
    };
    $backUrl = match ($mode) {
        'landlord' => route('landlord.passport.index', $property),
        'caretaker' => route('caretaker.passport.index'),
        'tenant' => route('tenant.dashboard'),
        'super_admin' => route('super_admin.passports.index'),
    };
    $occupancy = $overview['occupancy'] ?? null;

    $categoryIcons = [
        'tenancy' => 'bi-people',
        'inspection' => 'bi-clipboard-check',
        'maintenance' => 'bi-tools',
        'document' => 'bi-file-earmark-text',
        'condition' => 'bi-clipboard-heart',
        'status' => 'bi-arrow-repeat',
    ];
    $tab = $tab ?? 'overview';
    $navTabs = [
        'overview' => 'Overview',
        'history' => 'History',
        'inspections' => 'Inspections',
        'maintenance' => 'Maintenance',
        'documents' => 'Documents',
    ];
@endphp

<div class="kp-page-header">
    <div>
        <h1 class="kp-page-title">{{ $unit->unit_number }} · {{ $property->name }}</h1>
        <p class="kp-page-subtitle">
            <i class="bi bi-geo-alt me-1"></i>{{ $property->location }}
            &middot; <span class="text-muted">Passport</span> <strong>{{ $unit->passportCode() }}</strong>
            &middot;
            @if($isTenant || $isSuper)
                <span class="kp-badge {{ $unit->statusBadgeClass() }}">{{ ucfirst($unit->status) }}</span>
            @else
                <span class="kp-badge {{ $unit->statusBadgeClass() }}">{{ ucfirst($unit->status) }}</span>
            @endif
        </p>
    </div>
    <div class="kp-page-actions">
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
        @if($isLandlord)
            <a href="{{ route('landlord.units.edit', [$property, $unit]) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i> Edit Unit
            </a>
        @endif
    </div>
</div>

@if($isSuper)
    <div class="alert alert-info d-flex align-items-center">
        <i class="bi bi-shield-lock me-2"></i>
        Read-only administrative view. Actions are disabled for your role.
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="kp-card kp-kpi">
            <div class="kp-kpi-top">
                <div class="kp-kpi-icon purple"><i class="bi bi-clipboard-check"></i></div>
            </div>
            <div class="kp-kpi-value">{{ $overview['counts']['inspections'] }}</div>
            <p class="kp-kpi-label">Inspections recorded</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kp-card kp-kpi">
            <div class="kp-kpi-top">
                <div class="kp-kpi-icon red"><i class="bi bi-tools"></i></div>
            </div>
            <div class="kp-kpi-value">{{ $condition['open_maintenance']->count() }}</div>
            <p class="kp-kpi-label">Open maintenance</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kp-card kp-kpi">
            <div class="kp-kpi-top">
                <div class="kp-kpi-icon gold"><i class="bi bi-file-earmark-text"></i></div>
            </div>
            <div class="kp-kpi-value">{{ $overview['counts']['documents'] }}</div>
            <p class="kp-kpi-label">Documents</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="kp-card kp-kpi">
            <div class="kp-kpi-top">
                <div class="kp-kpi-icon green"><i class="bi bi-calendar-check"></i></div>
            </div>
            <div class="kp-kpi-value" style="font-size:1rem;">
                {{ $condition['last_inspection_date'] ? \Illuminate\Support\Carbon::parse($condition['last_inspection_date'])->format('d M Y') : 'Not yet recorded' }}
            </div>
            <p class="kp-kpi-label">Last inspection</p>
        </div>
    </div>
</div>

<ul class="nav nav-pills kp-tabs mb-4">
    @foreach($navTabs as $key => $label)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ $showUrl($key) }}">{{ $label }}</a>
        </li>
    @endforeach
</ul>

{{-- ==================== OVERVIEW ==================== --}}
@if($tab === 'overview')
    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Unit details</h2></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Unit number</div>
                        <div class="fw-semibold">{{ $unit->unit_number }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Passport code</div>
                        <div class="fw-semibold">{{ $unit->passportCode() }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Rent amount</div>
                        <div class="fw-semibold">KES {{ number_format((float) $unit->rent_amount, 2) }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Occupancy</div>
                        <div><span class="kp-badge {{ $unit->statusBadgeClass() }}">{{ ucfirst($unit->status) }}</span></div>
                    </div>
                    @if($unit->description)
                        <div class="col-12">
                            <div class="text-muted small">Description</div>
                            <div>{{ $unit->description }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Current tenancy</h2></div>
                @if($overview['active_tenant'])
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Tenant</div>
                            <div class="fw-semibold">{{ $overview['active_tenant']->full_name }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Move-in date</div>
                            <div class="fw-semibold">{{ optional($overview['move_in_date'])->format('d M Y') ?? '—' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Phone</div>
                            <div class="fw-semibold">{{ $isTenant ? ($overview['active_tenant']->phone ?? '—') : ($overview['active_tenant']->phone ?? '—') }}</div>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">This unit is currently vacant.</p>
                    @if($overview['last_move_out'])
                        <p class="text-muted mb-0 mt-1">Last tenancy ended {{ optional($overview['last_move_out']->move_out_date)->format('d M Y') }}.</p>
                    @endif
                @endif
            </div>

            <div class="kp-card">
                <div class="kp-card-header">
                    <h2 class="kp-card-title">Condition summary</h2>
                    <span class="kp-card-subtitle">Factual findings, separate from assessments</span>
                </div>
                @if(! $condition['has_data'])
                    <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Inspection not yet recorded.</p>
                @else
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="text-muted small">Most recent inspection</div>
                            <div class="fw-semibold">
                                {{ $condition['last_inspection_date'] ? \Illuminate\Support\Carbon::parse($condition['last_inspection_date'])->format('d M Y') : 'Not yet recorded' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Outstanding inspections</div>
                            <div class="fw-semibold">{{ $condition['outstanding_inspections'] }} draft</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Open maintenance</div>
                            <div class="fw-semibold">{{ $condition['open_maintenance']->count() }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted small">Most recent repair</div>
                            <div class="fw-semibold">
                                {{ $condition['last_repair'] ? optional($condition['last_repair']->resolved_at)->format('d M Y') : '—' }}
                            </div>
                        </div>
                        @if($condition['concerns']->isNotEmpty())
                            <div class="col-12">
                                <div class="text-muted small">Documented concerns</div>
                                <ul class="mb-0 ps-3">
                                    @foreach($condition['concerns'] as $concern)
                                        <li>{{ $concern }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            @if($isLandlord)
                <div class="kp-card mt-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Record condition assessment</h2></div>
                    <p class="text-muted" style="font-size:0.85rem;">
                        Assessments are your summary judgement and are kept separate from factual inspection findings.
                    </p>
                    <form method="POST" action="{{ route('landlord.passport.conditions.store', [$property, $unit]) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label" for="assessed_at">Assessment date</label>
                                <input type="date" name="assessed_at" id="assessed_at" class="form-control"
                                       value="{{ old('assessed_at', now()->toDateString()) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="rating">Overall rating <span class="text-muted fw-normal">(optional)</span></label>
                                <select name="rating" id="rating" class="form-select">
                                    <option value="">No rating</option>
                                    @foreach($conditionRatings as $value => $label)
                                        <option value="{{ $value }}" @selected(old('rating') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="summary">Summary</label>
                                <textarea name="summary" id="summary" rows="3" class="form-control" required>{{ old('summary') }}</textarea>
                                @error('summary')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3"><i class="bi bi-plus-circle me-1"></i> Save Assessment</button>
                    </form>
                </div>
            @endif
        </div>

        <div class="col-12 col-xl-4">
            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Recent activity</h2></div>
                @include('passport.partials.timeline', ['events' => $recentActivity, 'categoryIcons' => $categoryIcons, 'compact' => true])
            </div>

            @if($assessments->isNotEmpty() && ! $isTenant)
                <div class="kp-card">
                    <div class="kp-card-header"><h2 class="kp-card-title">Condition assessments</h2></div>
                    @foreach($assessments as $assessment)
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="kp-badge {{ $assessment->ratingBadgeClass() }}">{{ $assessment->ratingLabel() }}</span>
                                <span class="text-muted small">{{ optional($assessment->assessed_at)->format('d M Y') }}</span>
                            </div>
                            <p class="mb-1" style="font-size:0.88rem;">{{ $assessment->summary }}</p>
                            <div class="text-muted small">
                                By {{ $assessment->assessedBy->name ?? $assessment->createdBy->name ?? '—' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif

{{-- ==================== HISTORY ==================== --}}
@if($tab === 'history')
    <div class="kp-card mb-4">
        <form method="GET" action="{{ route($mode === 'landlord' ? 'landlord.passport.show' : ($mode === 'caretaker' ? 'caretaker.passport.show' : ($mode === 'tenant' ? 'tenant.passport.show' : 'super_admin.passports.show')), $mode === 'landlord' ? [$property, $unit] : ($mode === 'caretaker' ? [$unit] : ($mode === 'super_admin' ? [$unit] : []))) }}" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="history">
            <div class="col-md-3">
                <label class="form-label" for="category">Event type</label>
                <select name="category" id="category" class="form-select">
                    <option value="">All events</option>
                    @foreach(['tenancy' => 'Tenancy', 'inspection' => 'Inspection', 'maintenance' => 'Maintenance', 'document' => 'Document', 'condition' => 'Condition', 'status' => 'Unit status'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['category'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="from">From</label>
                <input type="date" name="from" id="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="to">To</label>
                <input type="date" name="to" id="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="{{ $showUrl('history') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Timeline</h2>
            <span class="kp-card-subtitle">{{ $timeline->count() }} event(s)</span>
        </div>
        @include('passport.partials.timeline', ['events' => $timeline, 'categoryIcons' => $categoryIcons])
    </div>
@endif

{{-- ==================== INSPECTIONS ==================== --}}
@if($tab === 'inspections')
    @if($canManage)
        <div class="kp-card mb-4">
            <div class="kp-card-header"><h2 class="kp-card-title">Start a new inspection</h2></div>
            <form method="POST" action="{{ $isLandlord ? route('landlord.passport.inspections.store', [$property, $unit]) : route('caretaker.passport.inspections.store', $unit) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="type">Inspection type</label>
                    <select name="type" id="type" class="form-select" required>
                        @foreach($inspectionTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="inspection_date">Inspection date</label>
                    <input type="date" name="inspection_date" id="inspection_date" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> New Inspection</button>
                </div>
            </form>
        </div>
    @endif

    @if($comparison)
        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Move-in vs move-out comparison</h2>
                <span class="kp-card-subtitle">Matched by checklist item</span>
            </div>
            <div class="alert alert-warning" style="font-size:0.83rem;">
                <i class="bi bi-exclamation-triangle me-1"></i>
                A difference between inspections is not, by itself, proof the tenant caused damage. Review the evidence and record your decision.
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Move-in</th>
                            <th>Move-out</th>
                            <th>Changed?</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparison as $row)
                            <tr>
                                <td>
                                    <span class="text-muted small d-block">{{ $row['category'] }}</span>
                                    {{ $row['label'] }}
                                </td>
                                <td>{{ $row['move_in_label'] }}</td>
                                <td>{{ $row['move_out_label'] }}</td>
                                <td>
                                    @if($row['changed'])
                                        <span class="kp-badge kp-badge-warning">Changed</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @forelse($inspections as $inspection)
        <div class="kp-card mb-3">
            <div class="kp-card-header">
                <div>
                    <h2 class="kp-card-title">{{ $inspection->typeLabel() }}</h2>
                    <span class="kp-card-subtitle">
                        {{ optional($inspection->inspection_date)->format('d M Y') }}
                        @if($inspection->tenant) &middot; {{ $inspection->tenant->full_name }} @endif
                    </span>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="kp-badge {{ $inspection->statusBadgeClass() }}">{{ $inspection->statusLabel() }}</span>
                    @if($inspection->isAcknowledged())
                        <span class="kp-badge kp-badge-purple">Acknowledged</span>
                    @endif
                    @if($inspectionUrl($inspection))
                        <a href="{{ $inspectionUrl($inspection) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    @endif
                </div>
            </div>
            @if($inspection->isSubmitted())
                <div class="row g-2 mb-2">
                    <div class="col-md-4"><span class="text-muted small">Inspector:</span> <strong>{{ $inspection->inspectedBy->name ?? '—' }}</strong></div>
                    <div class="col-md-4"><span class="text-muted small">Keys returned:</span> <strong>{{ $inspection->keys_returned ? 'Yes' : 'No' }}</strong></div>
                    <div class="col-md-4"><span class="text-muted small">Recommendation:</span> <strong>{{ $inspection->recommendationLabel() }}</strong></div>
                    <div class="col-md-4"><span class="text-muted small">Electricity meter:</span> <strong>{{ $inspection->electricity_meter ?: '—' }}</strong></div>
                    <div class="col-md-4"><span class="text-muted small">Water meter:</span> <strong>{{ $inspection->water_meter ?: '—' }}</strong></div>
                    <div class="col-md-4"><span class="text-muted small">Cleanliness:</span> <strong>{{ ucfirst($inspection->cleanliness ?? '—') }}</strong></div>
                </div>
                @if($inspection->general_notes)
                    <p class="mb-2" style="font-size:0.88rem;"><span class="text-muted">Notes:</span> {{ $inspection->general_notes }}</p>
                @endif
                @if(! empty($inspection->photoUrls()))
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @foreach($inspection->photoUrls() as $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener">
                                <img src="{{ $url }}" alt="Inspection photo" class="rounded" style="width:72px;height:72px;object-fit:cover;">
                            </a>
                        @endforeach
                    </div>
                @endif
                @if($inspection->isAcknowledged())
                    <div class="alert alert-light mb-0" style="font-size:0.83rem;">
                        <i class="bi bi-check2-circle me-1"></i>
                        Acknowledged by {{ $inspection->acknowledgement_name ?? ($inspection->acknowledgedBy->name ?? 'tenant') }}
                        on {{ optional($inspection->acknowledged_at)->format('d M Y H:i') }}.
                        Acknowledgement confirms receipt only.
                        @if($inspection->acknowledgement_note)
                            <div class="mt-1 text-muted">“{{ $inspection->acknowledgement_note }}”</div>
                        @endif
                    </div>
                @elseif($isTenant)
                    <form method="POST" action="{{ route('tenant.passport.inspections.acknowledge', $inspection) }}" class="mt-3">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label" for="acknowledgement_name_{{ $inspection->id }}">Your name</label>
                                <input type="text" name="acknowledgement_name" id="acknowledgement_name_{{ $inspection->id }}"
                                       class="form-control" value="{{ auth()->user()->name }}">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label" for="acknowledgement_note_{{ $inspection->id }}">Comment <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="text" name="acknowledgement_note" id="acknowledgement_note_{{ $inspection->id }}" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="acknowledge" value="1" id="ack_{{ $inspection->id }}" required>
                                    <label class="form-check-label small" for="ack_{{ $inspection->id }}">I have reviewed this report</label>
                                </div>
                                <button class="btn btn-primary w-100 btn-sm">Acknowledge</button>
                            </div>
                        </div>
                    </form>
                @endif
            @else
                <p class="text-muted mb-0">
                    This inspection is still a draft.
                    @if($canManage && $inspectionUrl($inspection))
                        <a href="{{ $inspectionUrl($inspection) }}">Continue editing</a>.
                    @endif
                </p>
            @endif
        </div>
    @empty
        <div class="kp-card">
            <div class="kp-empty">
                <i class="bi bi-clipboard"></i>
                <p class="mb-0">No inspections recorded yet.</p>
            </div>
        </div>
    @endforelse
@endif

{{-- ==================== MAINTENANCE ==================== --}}
@if($tab === 'maintenance')
    <div class="kp-card">
        <div class="kp-card-header">
            <h2 class="kp-card-title">Maintenance history</h2>
            <span class="kp-card-subtitle">Reuses the existing maintenance records</span>
        </div>
        @if($maintenance->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-tools"></i>
                <p class="mb-0">No maintenance has been recorded for this unit.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Issue</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported</th>
                            <th>Assigned</th>
                            <th>Resolved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($maintenance as $request)
                            <tr>
                                <td class="fw-semibold">{{ $request->title ?: '—' }}</td>
                                <td>{{ $request->categoryLabel() }}</td>
                                <td><span class="kp-badge kp-badge-{{ $request->priorityColor() === 'secondary' ? 'muted' : $request->priorityColor() }}">{{ ucfirst($request->priority) }}</span></td>
                                <td><span class="kp-badge kp-badge-{{ $request->statusColor() === 'secondary' ? 'muted' : ($request->statusColor() === 'info' ? 'purple' : $request->statusColor()) }}">{{ ucfirst(str_replace('_', ' ', $request->status)) }}</span></td>
                                <td>{{ optional($request->created_at)->format('d M Y') }}</td>
                                <td>{{ $request->assignedTo->name ?? '—' }}</td>
                                <td>{{ $request->resolved_at ? \Illuminate\Support\Carbon::parse($request->resolved_at)->format('d M Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

{{-- ==================== DOCUMENTS ==================== --}}
@if($tab === 'documents')
    @if($canManage)
        <div class="kp-card mb-4">
            <div class="kp-card-header"><h2 class="kp-card-title">Upload document or evidence</h2></div>
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $isLandlord ? route('landlord.passport.documents.store', [$property, $unit]) : route('caretaker.passport.documents.store', $unit) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="title">Title</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="type">Type</label>
                        <select name="type" id="type" class="form-select" required>
                            @foreach($documentTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="visibility">Visibility</label>
                        <select name="visibility" id="visibility" class="form-select" @disabled(! $isLandlord)>
                            @foreach($documentVisibilities as $value => $label)
                                <option value="{{ $value }}" @selected(old('visibility') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if(! $isLandlord)
                            <input type="hidden" name="visibility" value="{{ \App\Models\UnitDocument::VISIBILITY_LANDLORD }}">
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="file">File <span class="text-muted fw-normal">(pdf, image, doc, sheet; max 10MB)</span></label>
                        <input type="file" name="file" id="file" class="form-control" required>
                        @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="note">Note <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" name="note" id="note" class="form-control" value="{{ old('note') }}">
                    </div>
                </div>
                <button class="btn btn-primary mt-3"><i class="bi bi-upload me-1"></i> Upload</button>
            </form>
        </div>
    @endif

    <div class="kp-card">
        <div class="kp-card-header"><h2 class="kp-card-title">Documents</h2></div>
        @if($documents->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-folder2-open"></i>
                <p class="mb-0">{{ $isTenant ? 'No documents have been shared with you.' : 'No documents have been uploaded for this unit.' }}</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Visibility</th>
                            <th>Uploaded</th>
                            <th>By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $document)
                            <tr>
                                <td class="fw-semibold">{{ $document->title }}</td>
                                <td>{{ $document->typeLabel() }}</td>
                                <td>
                                    @if($document->isTenantVisible())
                                        <span class="kp-badge kp-badge-purple">Shared with tenant</span>
                                    @else
                                        <span class="kp-badge kp-badge-muted">Landlord only</span>
                                    @endif
                                </td>
                                <td>{{ optional($document->created_at)->format('d M Y') }}</td>
                                <td>{{ $document->uploadedBy->name ?? '—' }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="{{ route('passport.documents.download', $document) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        @if($isLandlord)
                                            <form method="POST" action="{{ route('landlord.passport.documents.destroy', $document) }}"
                                                  onsubmit="return confirm('Remove this document?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
@endsection

@push('styles')
<style>
    .kp-tabs .nav-link {
        border-radius: 10px;
        color: var(--kp-muted);
        font-weight: 600;
        font-size: 0.86rem;
        padding: 8px 16px;
    }
    .kp-tabs .nav-link.active {
        background: var(--kp-royal);
        color: #fff;
    }
</style>
@endpush

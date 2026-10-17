@extends('layouts.app')

@section('page-title', 'Move-out Details')

@section('content')
    @php
        $tenant = $moveOut->tenant;
        $inspection = $moveOut->inspections->sortByDesc('id')->first();
        $reviewStatuses = array_values(array_filter(
            $moveOut->transitions()[$moveOut->status] ?? [],
            fn ($s) => in_array($s, ['under_review', 'awaiting_tenant_info', 'approved', 'rejected'], true)
        ));
        $canClear = in_array($moveOut->status, ['approved', 'inspection_scheduled', 'inspection_completed', 'awaiting_clearance'], true);
        $canAssign = in_array($moveOut->status, ['approved', 'inspection_scheduled'], true);
    @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">{{ $tenant->full_name ?? 'Move-out request' }}</h1>
            <p class="kp-page-subtitle">
                {{ $moveOut->property->name ?? '—' }} / {{ $moveOut->unit->unit_number ?? '—' }}
                &middot; <span class="kp-badge {{ $moveOut->statusBadgeClass() }}">{{ $moveOut->statusLabelText() }}</span>
            </p>
        </div>
        <div class="kp-page-actions">
            @if($moveOut->settlement)
                <a href="{{ route('landlord.settlements.show', $moveOut->settlement) }}" class="btn btn-outline-primary">
                    <i class="bi bi-file-earmark-text me-2"></i> Deposit Settlement
                </a>
            @else
                <form method="POST" action="{{ route('landlord.settlements.store', $moveOut) }}">
                    @csrf
                    <button class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-plus me-2"></i> Start Deposit Settlement
                    </button>
                </form>
            @endif
            <a href="{{ route('landlord.move-outs.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back to Move-outs
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Request details</h2></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">Proposed move-out date</div>
                        <div class="fw-semibold">{{ optional($moveOut->proposed_move_out_date)->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Earliest permitted (notice period)</div>
                        <div class="fw-semibold">{{ optional($moveOut->earliest_permitted_date)->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Notice period</div>
                        <div class="fw-semibold">{{ rtrim(rtrim(number_format((float) $moveOut->notice_period_months, 2), '0'), '.') }} month(s)</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Submitted</div>
                        <div class="fw-semibold">{{ optional($moveOut->submitted_at)->format('d M Y H:i') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Actual move-out date</div>
                        <div class="fw-semibold">{{ optional($moveOut->actual_move_out_date)->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">Reviewed by</div>
                        <div class="fw-semibold">{{ $moveOut->reviewedBy->name ?? '—' }}</div>
                    </div>
                    @if($moveOut->reason)
                        <div class="col-12">
                            <div class="text-muted small">Reason given by tenant</div>
                            <div>{{ $moveOut->reason }}</div>
                        </div>
                    @endif
                    @if($moveOut->decision_reason)
                        <div class="col-12">
                            <div class="text-muted small">Decision reason</div>
                            <div>{{ $moveOut->decision_reason }}</div>
                        </div>
                    @endif
                    @if($moveOut->clarification_message)
                        <div class="col-12">
                            <div class="text-muted small">Information requested from tenant</div>
                            <div>{{ $moveOut->clarification_message }}</div>
                        </div>
                    @endif
                    @if($moveOut->notes)
                        <div class="col-12">
                            <div class="text-muted small">Clearance notes</div>
                            <div>{{ $moveOut->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Inspection</h2></div>
                @if(! $inspection)
                    <p class="text-muted mb-0">No move-out inspection has been assigned yet.</p>
                @else
                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <div class="text-muted small">Caretaker</div>
                            <div class="fw-semibold">{{ $inspection->caretaker->name ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-muted small">Status</div>
                            <span class="kp-badge {{ $inspection->statusBadgeClass() }}">{{ $inspection->statusLabel() }}</span>
                        </div>
                        <div>
                            <div class="text-muted small">Scheduled</div>
                            <div class="fw-semibold">{{ optional($inspection->scheduled_at)->format('d M Y H:i') ?? '—' }}</div>
                        </div>
                        <div>
                            <div class="text-muted small">Completed</div>
                            <div class="fw-semibold">{{ optional($inspection->completed_at)->format('d M Y H:i') ?? '—' }}</div>
                        </div>
                    </div>

                    @if($inspection->isSubmitted())
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Checklist item</th><th>Condition</th><th>Notes</th></tr></thead>
                                <tbody>
                                    @foreach($inspection->items->groupBy('category') as $category => $items)
                                        @foreach($items as $item)
                                            <tr>
                                                <td>
                                                    @if($loop->first)<span class="text-muted small d-block">{{ $category }}</span>@endif
                                                    {{ $item->label }}
                                                </td>
                                                <td>{{ $item->conditionLabel() }}</td>
                                                <td class="text-muted">{{ $item->comment ?: '—' }}</td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-md-4"><span class="text-muted small">Keys returned:</span> <strong>{{ $inspection->keys_returned ? 'Yes' : 'No' }}</strong></div>
                            <div class="col-md-4"><span class="text-muted small">Electricity meter:</span> <strong>{{ $inspection->electricity_meter ?: '—' }}</strong></div>
                            <div class="col-md-4"><span class="text-muted small">Water meter:</span> <strong>{{ $inspection->water_meter ?: '—' }}</strong></div>
                            <div class="col-md-4"><span class="text-muted small">Cleanliness:</span> <strong>{{ ucfirst($inspection->cleanliness ?? '—') }}</strong></div>
                            <div class="col-md-8"><span class="text-muted small">Recommendation:</span> <strong>{{ $inspection->recommendationLabel() }}</strong></div>
                            @if($inspection->general_notes)
                                <div class="col-12"><span class="text-muted small">Notes:</span> {{ $inspection->general_notes }}</div>
                            @endif
                            @if(! empty($inspection->photoUrls()))
                                <div class="col-12">
                                    <span class="text-muted small">Photos:</span>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        @foreach($inspection->photoUrls() as $url)
                                            <a href="{{ $url }}" target="_blank" rel="noopener">
                                                <img src="{{ $url }}" alt="Inspection photo" class="rounded"
                                                     style="width:72px;height:72px;object-fit:cover;">
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if($canAssign)
                        <form method="POST" action="{{ route('landlord.move-outs.inspection.assign', $moveOut) }}" class="row g-2 mt-3 align-items-end">
                            @csrf
                            <div class="col-md-5">
                                <label class="form-label" for="caretaker_id">Assign caretaker</label>
                                <select name="caretaker_id" id="caretaker_id" class="form-select" required>
                                    <option value="">Select caretaker...</option>
                                    @foreach($caretakers as $caretaker)
                                        <option value="{{ $caretaker->id }}">{{ $caretaker->name }}</option>
                                    @endforeach
                                </select>
                                @if($caretakers->isEmpty())
                                    <div class="form-text text-danger">No caretakers are assigned to this property.</div>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="scheduled_at">Schedule (optional)</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" @disabled($caretakers->isEmpty())>
                                    <i class="bi bi-person-plus me-1"></i> Assign
                                </button>
                            </div>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="kp-card mb-4">
                <div class="kp-card-header"><h2 class="kp-card-title">Security deposit</h2></div>
                @if(! $deposit)
                    <p class="text-muted mb-0">No deposit is held for this tenancy.</p>
                @else
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status</span>
                        <span class="kp-badge {{ $deposit->statusBadgeClass() }}">{{ $deposit->statusLabel() }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Required</span><span class="fw-semibold">KES {{ number_format((float) $deposit->required_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Received</span><span class="fw-semibold">KES {{ number_format((float) $deposit->received_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Approved deductions</span><span class="fw-semibold">KES {{ number_format($deposit->approvedDeductions(), 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-3"><span class="text-muted">Net refundable</span><span class="fw-semibold">KES {{ number_format($deposit->netRefundable(), 2) }}</span></div>
                    <a href="{{ route('landlord.deposits.show', $deposit) }}" class="btn btn-outline-primary w-100">Manage Deposit</a>
                @endif
            </div>

            @if(count($reviewStatuses) && $moveOut->isOpen())
                <div class="kp-card mb-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Review request</h2></div>
                    <form method="POST" action="{{ route('landlord.move-outs.review', $moveOut) }}">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label" for="status">New status</label>
                            <select name="status" id="status" class="form-select" required>
                                @foreach($reviewStatuses as $value)
                                    <option value="{{ $value }}">{{ \App\Models\MoveOutRequest::statusLabel($value) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="decision_reason">Decision reason <span class="text-muted fw-normal">(required to reject)</span></label>
                            <textarea name="decision_reason" id="decision_reason" rows="2" class="form-control">{{ old('decision_reason') }}</textarea>
                            @error('decision_reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="clarification_message">Message to tenant <span class="text-muted fw-normal">(required to request info)</span></label>
                            <textarea name="clarification_message" id="clarification_message" rows="2" class="form-control">{{ old('clarification_message') }}</textarea>
                            @error('clarification_message')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary w-100">Update Status</button>
                    </form>
                </div>
            @endif

            @if($canClear)
                <div class="kp-card">
                    <div class="kp-card-header"><h2 class="kp-card-title">Final clearance</h2></div>
                    <p class="text-muted" style="font-size:0.85rem;">
                        Clearing closes the tenancy and marks the unit vacant. Ensure the deposit has been settled.
                    </p>
                    <form method="POST" action="{{ route('landlord.move-outs.clear', $moveOut) }}">
                        @csrf @method('PATCH')
                        <div class="mb-3">
                            <label class="form-label" for="actual_move_out_date">Actual move-out date</label>
                            <input type="date" name="actual_move_out_date" id="actual_move_out_date" class="form-control"
                                   value="{{ old('actual_move_out_date', optional($moveOut->proposed_move_out_date)->toDateString()) }}" required>
                            @error('actual_move_out_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="clearance_decision">Clearance decision</label>
                            <select name="clearance_decision" id="clearance_decision" class="form-select" required>
                                <option value="cleared">Cleared</option>
                                <option value="cleared_with_notes">Cleared with notes</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="notes">Notes <span class="text-muted fw-normal">(required with "cleared with notes")</span></label>
                            <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes') }}</textarea>
                            @error('notes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-success w-100">Complete &amp; Close Tenancy</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('page-title', 'Submit Notice to Vacate')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Submit Notice to Vacate</h1>
            <p class="kp-page-subtitle">Tell us when you plan to move out of {{ $tenant->property->name ?? '—' }} / {{ $tenant->unit->unit_number ?? '—' }}.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('tenant.move-out.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="kp-card">
                <div class="kp-card-header"><h2 class="kp-card-title">Notice details</h2></div>

                <div class="alert alert-info" style="font-size:0.87rem;">
                    Your notice period is {{ rtrim(rtrim(number_format($months, 2), '0'), '.') }} month(s). The earliest permitted move-out date is
                    <strong>{{ $earliest->format('d M Y') }}</strong>.
                </div>

                <div class="p-3 mb-3 rounded-3" style="background:#FDF2F2;border-left:4px solid #DC4545;">
                    <h2 class="h6 fw-bold mb-2" style="color:#DC4545;letter-spacing:0.04em;">NOTICE TO VACATE</h2>
                    <p class="mb-2" style="font-size:0.88rem;">
                        This form is a formal <strong>Notice to Vacate</strong> for
                        <strong>{{ $tenant->property->name ?? 'your property' }}</strong>
                        @if($tenant->unit) / <strong>{{ $tenant->unit->unit_number }}</strong>@endif.
                        Submitting it starts the move-out process described below.
                    </p>
                    <ul class="mb-0 ps-3" style="font-size:0.85rem;">
                        <li>You commit to vacating on or after the proposed move-out date.</li>
                        <li>The notice period of {{ rtrim(rtrim(number_format($months, 2), '0'), '.') }} month(s) must be served before vacating.</li>
                        <li>Rent remains payable up to the actual move-out date.</li>
                        <li>A move-out inspection will be arranged before any deposit is refunded.</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('tenant.move-out.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="proposed_move_out_date">Proposed move-out date</label>
                        <input type="date" name="proposed_move_out_date" id="proposed_move_out_date"
                               class="form-control" min="{{ $earliest->toDateString() }}"
                               value="{{ old('proposed_move_out_date', $earliest->toDateString()) }}" required>
                        @error('proposed_move_out_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reason">Reason <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea name="reason" id="reason" rows="3" class="form-control"
                                  placeholder="You may share the reason for moving out...">{{ old('reason') }}</textarea>
                        @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="acknowledged" id="acknowledged" value="1"
                               @checked(old('acknowledged')) required>
                        <label class="form-check-label" for="acknowledged">
                            I have read and understood this <strong>Notice to Vacate</strong> and confirm I wish to submit it.
                        </label>
                        @error('acknowledged')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i> Submit Notice
                        </button>
                        <a href="{{ route('tenant.move-out.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="kp-card">
                <div class="kp-card-header"><h2 class="kp-card-title">What happens next</h2></div>
                <ol class="mb-0 ps-3" style="font-size:0.88rem;">
                    <li class="mb-1">Your landlord reviews the request.</li>
                    <li class="mb-1">A move-out inspection is scheduled with the caretaker.</li>
                    <li class="mb-1">Any deposit deductions are proposed and approved.</li>
                    <li>Your deposit is refunded and the tenancy is closed.</li>
                </ol>
            </div>
        </div>
    </div>
@endsection

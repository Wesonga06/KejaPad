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

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Request payment reversal</h1>
            <p class="kp-page-subtitle">Payment {{ $payment->reference ?? '#'.$payment->id }} · {{ $payment->tenant->full_name }} · KSh {{ number_format($payment->amount, 2) }}</p>
        </div>
    </div>
    <div class="alert alert-info">This requests an accounting reversal. It does not initiate an M-Pesa refund. A Super Admin must approve it.</div>
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('landlord.payments.reversals.store', $payment) }}" class="kp-card kp-reversal-form d-grid gap-3">
        @csrf
        <label>Reversal amount (KSh)<input class="form-control" type="number" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}" required></label>
        <label>Part taken from held credit (available KSh {{ number_format($availableCredit, 2) }})<input class="form-control" type="number" name="reversed_credit" min="0" step="0.01" value="{{ old('reversed_credit', '0.00') }}" required></label>
        <fieldset><legend class="h6">Part reversed from rent allocations</legend>
            @forelse($lines as $line)
                <label class="d-block">{{ $line->rentRecord?->period_label ?? $line->rentRecord?->due_date?->format('F Y') ?? 'Rent period' }} — allocated KSh {{ number_format($line->amount, 2) }}, available KSh {{ number_format($line->reversible, 2) }}
                    <input class="form-control" type="number" name="allocations[{{ $line->id }}]" min="0.01" max="{{ number_format($line->reversible, 2, '.', '') }}" step="0.01" value="{{ old('allocations.'.$line->id) }}">
                </label>
            @empty <p class="text-muted">No rent allocation is available to reverse.</p> @endforelse
        </fieldset>
        <label>Reason<textarea name="reason" class="form-control" maxlength="5000" required>{{ old('reason') }}</textarea></label>
        <p class="small text-muted">Credit plus allocation amounts must equal the reversal amount.</p>
        <button class="btn btn-primary">Submit for approval</button>
    </form>
</div>
@endsection

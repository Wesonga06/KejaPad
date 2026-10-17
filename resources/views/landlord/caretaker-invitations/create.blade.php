@extends('layouts.app')

@section('page-title', 'New Caretaker Invitation')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">New Caretaker Invitation</h1>
            <p class="kp-page-subtitle">Generate a secure, single-use invitation for a caretaker to manage one of your properties.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.caretaker-invitations.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="kp-card">
                @if($properties->isEmpty())
                    <div class="kp-empty">
                        <i class="bi bi-buildings"></i>
                        <p class="mb-2">You need at least one property before inviting a caretaker.</p>
                        <a href="{{ route('landlord.properties.create') }}" class="btn btn-primary">Add a property</a>
                    </div>
                @else
                    <form method="POST" action="{{ route('landlord.caretaker-invitations.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="property_id">Property the caretaker will manage <span class="text-danger">*</span></label>
                            <select name="property_id" id="property_id" class="form-select @error('property_id') is-invalid @enderror" required>
                                <option value="">Select a property...</option>
                                @foreach($properties as $property)
                                    <option value="{{ $property->id }}" @selected(old('property_id') == $property->id)>{{ $property->name }}</option>
                                @endforeach
                            </select>
                            @error('property_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="name">Caretaker name <span class="text-muted">(optional)</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name') }}" placeholder="e.g. Mary Wanjiku">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6 mb-3">
                                <label class="form-label fw-semibold" for="email">Caretaker email <span class="text-muted">(optional)</span></label>
                                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email') }}" placeholder="caretaker@email.com">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text" style="font-size:0.76rem;">
                                    If provided, only this email can redeem the invitation.
                                </div>
                            </div>
                        </div>

                        <div class="alert" style="background: rgba(89,38,200,0.06); border:1px solid rgba(89,38,200,0.16); color: var(--kp-text); font-size:0.85rem;">
                            The invitation code is single-use and expires automatically
                            ({{ (int) config('tenancy.invitation_expiry_days', 7) }} days). The caretaker must enter it on the registration form.
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-envelope-plus me-2"></i> Generate Invitation
                            </button>
                            <a href="{{ route('landlord.caretaker-invitations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('page-title', 'Add Tenant')


@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Add New Tenant</h1>
            <p class="kp-page-subtitle">Add the tenant, assign an available unit, and optionally create their login account.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('landlord.tenants.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="stat-card kp-form-wide">
        <form method="POST" action="{{ route('landlord.tenants.store') }}" id="tenantForm">
            @csrf

            {{-- Personal Information --}}
            <h2 class="kp-form-section">Personal Information</h2>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="full_name">Full Name</label>
                    <input type="text" name="full_name"
                           id="full_name"
                           class="form-control @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name') }}" placeholder="e.g. John Kamau">
                    @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="phone">Phone Number</label>
                    <input type="text" name="phone"
                           id="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}" placeholder="e.g. 0712345678">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="email">Email <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="email" name="email"
                           id="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" placeholder="e.g. john@email.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="national_id">National ID <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="national_id"
                           id="national_id"
                           class="form-control @error('national_id') is-invalid @enderror"
                           value="{{ old('national_id') }}" placeholder="e.g. 12345678">
                    @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Emergency Contact --}}
            <h2 class="kp-form-section mt-4">Emergency Contact</h2>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="emergency_contact_name">Contact Name <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="emergency_contact_name"
                           id="emergency_contact_name"
                           class="form-control @error('emergency_contact_name') is-invalid @enderror"
                           value="{{ old('emergency_contact_name') }}" placeholder="e.g. Jane Kamau">
                    @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="emergency_contact_phone">Contact Phone <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="emergency_contact_phone"
                           id="emergency_contact_phone"
                           class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                           value="{{ old('emergency_contact_phone') }}" placeholder="e.g. 0712345678">
                    @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Unit Assignment --}}
            <h2 class="kp-form-section mt-4">Unit Assignment</h2>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="propertySelect">Property</label>
                    <select name="property_id" id="propertySelect"
                            class="form-select @error('property_id') is-invalid @enderror">
                        <option value="">-- Select Property --</option>
                        @foreach($properties as $property)
                            <option value="{{ $property->id }}"
                                {{ old('property_id') == $property->id ? 'selected' : '' }}>
                                {{ $property->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('property_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="unitSelect">Unit</label>
                    <select name="unit_id" id="unitSelect"
                            class="form-select @error('unit_id') is-invalid @enderror">
                        <option value="">-- Select Property First --</option>
                    </select>
                    @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="move_in_date">Move-in Date</label>
                    <input type="date" name="move_in_date"
                           id="move_in_date"
                           class="form-control @error('move_in_date') is-invalid @enderror"
                           value="{{ old('move_in_date', date('Y-m-d')) }}">
                    @error('move_in_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="lease_end_date">Lease End Date <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="date" name="lease_end_date"
                           id="lease_end_date"
                           class="form-control @error('lease_end_date') is-invalid @enderror"
                           value="{{ old('lease_end_date') }}">
                    @error('lease_end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Only used to warn you before the tenancy term ends.</div>
                </div>
            </div>

            {{-- Login Account --}}
            <h2 class="kp-form-section mt-4">Tenant Login Account</h2>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="createAccount"
                       name="create_account" value="1" {{ old('create_account') ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="createAccount">
                    Create a login account for this tenant
                </label>
                <div class="text-muted" style="font-size:0.82rem;">
                    The tenant will be able to log in and view their rent balance and payment history.
                </div>
            </div>

            <div id="accountFields" style="display:none;">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="password">Password</label>
                        <input type="password" name="password"
                               id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Min 8 characters">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" for="password_confirmation">Confirm Password</label>
                        <input type="password" name="password_confirmation"
                               id="password_confirmation"
                               class="form-control" placeholder="Repeat password">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3">
                <i class="bi bi-person-plus me-2"></i> Add Tenant
            </button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    // Toggle account fields
    const createAccountToggle = document.getElementById('createAccount');
    const accountFields = document.getElementById('accountFields');

    createAccountToggle.addEventListener('change', function () {
        accountFields.style.display = this.checked ? 'block' : 'none';
    });

    // Load vacant units when property is selected
    const propertySelect = document.getElementById('propertySelect');
    const unitSelect = document.getElementById('unitSelect');

    propertySelect.addEventListener('change', function () {
        const propertyId = this.value;
        unitSelect.innerHTML = '<option value="">Loading...</option>';

        if (!propertyId) {
            unitSelect.innerHTML = '<option value="">-- Select Property First --</option>';
            return;
        }

        fetch(`/landlord/properties/${propertyId}/get-units`)
            .then(res => res.json())
            .then(units => {
                if (units.length === 0) {
                    unitSelect.innerHTML = '<option value="">No vacant units available</option>';
                } else {
                    unitSelect.innerHTML = '<option value="">-- Select Unit --</option>';
                    units.forEach(unit => {
                        unitSelect.innerHTML += `<option value="${unit.id}">
                            ${unit.unit_number} — KES ${Number(unit.rent_amount).toLocaleString()}
                        </option>`;
                    });
                }
            })
            .catch(() => {
                unitSelect.innerHTML = '<option value="">Error loading units</option>';
            });
    });
</script>
@endpush

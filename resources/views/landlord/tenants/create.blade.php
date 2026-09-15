@extends('layouts.app')

@section('page-title', 'Add Tenant')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}" class="nav-link active">
        <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}" class="nav-link">
    <i class="bi bi-cash-coin"></i> Rent
</a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Add New Tenant</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Fill in the tenant details below.</p>
        </div>
        <a href="{{ route('landlord.tenants.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:700px;">
        <form method="POST" action="{{ route('landlord.tenants.store') }}" id="tenantForm">
            @csrf

            {{-- Personal Information --}}
            <h6 class="fw-bold mb-3 pb-2 border-bottom">Personal Information</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Full Name</label>
                    <input type="text" name="full_name"
                           class="form-control @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name') }}" placeholder="e.g. John Kamau">
                    @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}" placeholder="e.g. 0712345678">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" placeholder="e.g. john@email.com">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">National ID <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="national_id"
                           class="form-control @error('national_id') is-invalid @enderror"
                           value="{{ old('national_id') }}" placeholder="e.g. 12345678">
                    @error('national_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Emergency Contact --}}
            <h6 class="fw-bold mb-3 pb-2 border-bottom mt-4">Emergency Contact</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Name <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="emergency_contact_name"
                           class="form-control @error('emergency_contact_name') is-invalid @enderror"
                           value="{{ old('emergency_contact_name') }}" placeholder="e.g. Jane Kamau">
                    @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Phone <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="text" name="emergency_contact_phone"
                           class="form-control @error('emergency_contact_phone') is-invalid @enderror"
                           value="{{ old('emergency_contact_phone') }}" placeholder="e.g. 0712345678">
                    @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Unit Assignment --}}
            <h6 class="fw-bold mb-3 pb-2 border-bottom mt-4">Unit Assignment</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Property</label>
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
                    <label class="form-label fw-semibold">Unit</label>
                    <select name="unit_id" id="unitSelect"
                            class="form-select @error('unit_id') is-invalid @enderror">
                        <option value="">-- Select Property First --</option>
                    </select>
                    @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Move-in Date</label>
                <input type="date" name="move_in_date"
                       class="form-control @error('move_in_date') is-invalid @enderror"
                       value="{{ old('move_in_date', date('Y-m-d')) }}">
                @error('move_in_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            {{-- Login Account --}}
            <h6 class="fw-bold mb-3 pb-2 border-bottom mt-4">Tenant Login Account</h6>

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
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Min 8 characters">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Confirm Password</label>
                        <input type="password" name="password_confirmation"
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
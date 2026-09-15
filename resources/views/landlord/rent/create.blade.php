@extends('layouts.app')

@section('page-title', 'Add Rent Record')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}" class="nav-link">
        <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}" class="nav-link active">
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
            <h5 class="fw-bold mb-1">Add Rent Record</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Create a rent record for a specific tenant and month.
            </p>
        </div>
        <a href="{{ route('landlord.rent.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="stat-card" style="max-width:600px;">
        <form method="POST" action="{{ route('landlord.rent.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Tenant</label>
                <select name="tenant_id" id="tenantSelect"
                        class="form-select @error('tenant_id') is-invalid @enderror">
                    <option value="">-- Select Tenant --</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}"
                                data-rent="{{ $tenant->unit->rent_amount }}"
                            {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>
                            {{ $tenant->full_name }} —
                            {{ $tenant->property->name }} /
                            Unit {{ $tenant->unit->unit_number }}
                        </option>
                    @endforeach
                </select>
                @error('tenant_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Amount Due (KES)</label>
                <input type="number" name="amount_due" id="amountDue"
                       class="form-control @error('amount_due') is-invalid @enderror"
                       value="{{ old('amount_due') }}"
                       placeholder="Auto-filled from unit rent" min="1" step="0.01">
                @error('amount_due')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Month</label>
                    <select name="month" class="form-select @error('month') is-invalid @enderror">
                        <option value="">-- Select Month --</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}"
                                {{ old('month', date('n')) == $m ? 'selected' : '' }}>
                                {{ date('F', mktime(0,0,0,$m,1)) }}
                            </option>
                        @endfor
                    </select>
                    @error('month')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Year</label>
                    <select name="year" class="form-select @error('year') is-invalid @enderror">
                        @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}"
                                {{ old('year', date('Y')) == $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                    @error('year')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Due Date</label>
                <input type="date" name="due_date"
                       class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date', date('Y-m-') . '05') }}">
                @error('due_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Notes <span class="text-muted fw-normal">(optional)</span>
                </label>
                <textarea name="notes" rows="2"
                          class="form-control @error('notes') is-invalid @enderror"
                          placeholder="Any additional notes">{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-cash-coin me-2"></i> Create Rent Record
            </button>
        </form>
    </div>
@endsection

@push('scripts')
<script>
    // Auto-fill rent amount when tenant is selected
    const tenantSelect = document.getElementById('tenantSelect');
    const amountDue    = document.getElementById('amountDue');

    tenantSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const rent     = selected.getAttribute('data-rent');
        if (rent) {
            amountDue.value = parseFloat(rent).toFixed(2);
        } else {
            amountDue.value = '';
        }
    });
</script>
@endpush
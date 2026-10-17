<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rent\RecordTenantPaymentRequest;
use App\Http\Requests\Tenant\StoreTenantRequest;
use App\Http\Requests\Tenant\UpdateTenantRequest;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\DepositService;
use App\Services\PaymentBehaviourService;
use App\Services\RentAllocationService;
use App\Services\RentStatusService;
use App\Services\TurnoverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LandlordTenantController extends Controller
{
    // List all tenants for this landlord
    public function index(Request $request)
    {
        $this->authorize('viewAny', Tenant::class);

        $query = Tenant::whereHas('property', function ($q) {
            $q->where('user_id', auth()->id());
        });

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($propertyId = $request->integer('property_id')) {
            $query->where('property_id', $propertyId);
        }

        if ($status = $request->string('status')->toString()) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $tenants = $query
            ->with(['property', 'unit'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $properties = Property::where('user_id', auth()->id())->orderBy('name')->get();

        return view('landlord.tenants.index', compact('tenants', 'properties'));
    }

    // Show create form
    public function create()
    {
        $this->authorize('create', Tenant::class);

        $properties = Property::where('user_id', auth()->id())->get();

        return view('landlord.tenants.create', compact('properties'));
    }

    // Get vacant units for a property (AJAX)
    public function getUnits(Property $property)
    {
        $this->authorize('view', $property);

        $units = $property->units()
            ->where('status', 'vacant')
            ->get(['id', 'unit_number', 'rent_amount']);

        return response()->json($units);
    }

    // Store new tenant
    public function store(StoreTenantRequest $request, DepositService $deposits, TurnoverService $turnovers)
    {
        // Make sure landlord owns this property
        $property = Property::findOrFail($request->property_id);
        $this->authorize('view', $property);

        // Make sure unit is vacant
        $unit = Unit::findOrFail($request->unit_id);
        if ($unit->status !== 'vacant') {
            return back()->withErrors(['unit_id' => 'This unit is not vacant.'])
                ->withInput();
        }

        $userId = null;

        // Create login account if requested
        if ($request->filled('create_account') && $request->filled('email')) {
            $request->validate([
                'password' => 'required|min:8|confirmed',
            ]);

            if (User::where('email', $request->email)->exists()) {
                return back()->withErrors(['email' => 'This email is already registered.'])
                    ->withInput();
            }

            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'tenant',
                'is_active' => true,
            ]);

            $userId = $user->id;
        }

        // Create tenant record
        $tenant = Tenant::create([
            'user_id' => $userId,
            'property_id' => $request->property_id,
            'unit_id' => $request->unit_id,
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'national_id' => $request->national_id,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'move_in_date' => $request->move_in_date,
            'lease_end_date' => $request->lease_end_date,
            'is_active' => true,
        ]);

        // Create the security deposit obligation for this tenancy.
        $deposits->ensureForTenant($tenant, (float) $unit->rent_amount);

        // A tenancy is only moved in once the deposit is received and verified.
        if (config('tenancy.require_deposit_before_move_in', true)) {
            $unit->update(['status' => 'reserved']);

            // Link this incoming tenancy to any ready turnover for the unit.
            $turnovers->linkIncomingTenant($unit->fresh(), $tenant, auth()->user());

            return redirect()->route('landlord.tenants.index')
                ->with('success', $tenant->full_name.' added. The unit is reserved pending '
                    .'verification of the KES '.number_format($deposits->requiredAmount((float) $unit->rent_amount), 2)
                    .' security deposit before move-in.');
        }

        // Mark unit as occupied
        $unit->update(['status' => 'occupied']);

        // Link this incoming tenancy to any ready turnover for the unit; the
        // unit is already occupied so the turnover completes immediately.
        $turnovers->linkIncomingTenant($unit->fresh(), $tenant, auth()->user());

        return redirect()->route('landlord.tenants.index')
            ->with('success', 'Tenant added successfully.');
    }

    // Show edit form
    public function edit(Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        $property = $tenant->property;

        $properties = Property::where('user_id', auth()->id())
            ->where('status', 'active')
            ->get();

        $units = $property->units()
            ->whereIn('status', ['vacant', 'occupied'])
            ->get();

        return view('landlord.tenants.edit', compact('tenant', 'properties', 'units'));
    }

    // Update tenant
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $tenant->update([
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'national_id' => $request->national_id,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'move_in_date' => $request->move_in_date,
            'move_out_date' => $request->move_out_date,
            'lease_end_date' => $request->lease_end_date,
        ]);

        return redirect()->route('landlord.tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    // Deactivate tenant
    public function deactivate(Tenant $tenant)
    {
        $this->authorize('deactivate', $tenant);

        $tenant->update([
            'is_active' => false,
            'move_out_date' => now()->toDateString(),
        ]);

        // Free up the unit
        $tenant->unit->update(['status' => 'vacant']);

        // Deactivate user account if linked
        if ($tenant->user_id) {
            $tenant->user->update(['is_active' => false]);
        }

        return back()->with('success', 'Tenant deactivated and unit marked as vacant.');
    }

    // Show tenant details, rent ledger, arrears breakdown, behaviour + notices
    public function show(Tenant $tenant, RentStatusService $status, PaymentBehaviourService $behaviour)
    {
        $this->authorize('view', $tenant);

        $tenant->load(['property', 'unit']);

        $summary = $status->summary($tenant);
        $breakdown = $status->breakdown($tenant);
        $indicators = $behaviour->indicators($tenant, now()->subMonths(6));
        $notices = $tenant->rentNotices()->latest('issued_at')->latest('id')->get();
        $payments = $tenant->payments()->latest('payment_date')->latest('id')->take(50)->get();

        $deposit = $tenant->securityDeposit;
        $activeMoveOut = $tenant->moveOutRequests()->whereNotIn('status', ['completed', 'rejected', 'cancelled'])->latest('submitted_at')->first();

        return view('landlord.tenants.show', compact(
            'tenant',
            'summary',
            'breakdown',
            'indicators',
            'notices',
            'payments',
            'deposit',
            'activeMoveOut'
        ));
    }

    // Record a payment (possibly partial) against a tenant's rent account
    public function recordPayment(RecordTenantPaymentRequest $request, Tenant $tenant, RentAllocationService $allocation)
    {
        $options = [
            'payment_date' => $request->payment_date,
            'reference' => $request->reference,
            'method' => $request->input('method', 'cash'),
            'source' => 'landlord',
            'recorded_by' => auth()->id(),
            'notes' => $request->input('notes'),
        ];

        if ($request->filled('rent_record_id')) {
            $options['policy'] = RentAllocationService::POLICY_MANUAL;
            $options['rent_record_id'] = $request->input('rent_record_id');
        } else {
            $options['policy'] = $request->input('policy', config('rent.allocation_policy'));
        }

        $payment = $allocation->recordPayment($tenant, (float) $request->amount_paid, $options);

        $message = 'Payment of KES '.number_format((float) $payment->amount, 2).' recorded for '.$tenant->full_name.'.';

        if ((float) $payment->unallocated_amount > 0) {
            $message .= ' KES '.number_format((float) $payment->unallocated_amount, 2).' held as credit.';
        }

        return back()->with('success', $message);
    }
}

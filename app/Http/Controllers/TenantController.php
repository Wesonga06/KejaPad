<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tenant\UpdateTenantProfileRequest;
use App\Models\RentRecord;
use App\Models\Tenant;
use App\Services\PaymentBehaviourService;
use App\Services\RentStatusService;
use Illuminate\Support\Facades\Hash;

class TenantController extends Controller
{
    public function dashboard(RentStatusService $status, PaymentBehaviourService $behaviour)
    {
        $tenant = Tenant::where('user_id', auth()->id())
            ->with(['property', 'unit'])
            ->first();

        if (! $tenant) {
            return view('tenant.no-profile');
        }

        // Current month rent record
        $currentRent = RentRecord::where('tenant_id', $tenant->id)
            ->where('month', date('n'))
            ->where('year', date('Y'))
            ->first();

        // Recent payment history
        $recentPayments = RentRecord::where('tenant_id', $tenant->id)
            ->whereIn('status', ['paid', 'partial', 'overdue'])
            ->latest()
            ->take(5)
            ->get();

        // Outstanding balance across all records
        $totalOutstanding = RentRecord::where('tenant_id', $tenant->id)
            ->get()
            ->sum(fn ($r) => $r->balance());

        // Derived, colour-coded status + payment behaviour (never rely on stored status)
        $rentStatus = $status->summary($tenant);
        $behaviour = $behaviour->indicators($tenant, now()->subMonths(6));

        return view('tenant.dashboard', compact(
            'tenant',
            'currentRent',
            'recentPayments',
            'totalOutstanding',
            'rentStatus',
            'behaviour'
        ));
    }

    public function paymentHistory(RentStatusService $status, PaymentBehaviourService $behaviour)
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();

        $base = RentRecord::where('tenant_id', $tenant->id);

        $totalRecords = (clone $base)->count();
        $paidCount = (clone $base)->where('status', 'paid')->count();
        $pendingCount = (clone $base)->whereIn('status', ['pending', 'partial', 'overdue'])->count();

        $summary = $status->summary($tenant);
        $totalBalance = $summary['outstanding'];
        $ledger = $status->breakdown($tenant);
        $payments = $tenant->payments()->latest('payment_date')->latest('id')->paginate(15);
        $behaviour = $behaviour->indicators($tenant, now()->subMonths(6));

        return view('tenant.payments', compact(
            'tenant',
            'payments',
            'totalRecords',
            'paidCount',
            'pendingCount',
            'totalBalance',
            'summary',
            'ledger',
            'behaviour'
        ));
    }

    public function profile()
    {
        $tenant = Tenant::where('user_id', auth()->id())
            ->with(['property', 'unit'])
            ->first();

        return view('tenant.profile', compact('tenant'));
    }

    public function updateProfile(UpdateTenantProfileRequest $request)
    {
        auth()->user()->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('current_password')) {
            if (! Hash::check($request->current_password, auth()->user()->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            auth()->user()->update([
                'password' => Hash::make($request->new_password),
            ]);
        }

        return back()->with('success', 'Profile updated successfully.');
    }
}

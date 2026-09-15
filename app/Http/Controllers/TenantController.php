<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use App\Models\RentRecord;

class TenantController extends Controller
{
    public function dashboard()
    {
        $tenant = Tenant::where('user_id', auth()->id())
                        ->with(['property', 'unit'])
                        ->first();

        if (!$tenant) {
            return view('tenant.no-profile');
        }

        // Current month rent record
        $currentRent = RentRecord::where('tenant_id', $tenant->id)
                                  ->where('month', date('n'))
                                  ->where('year', date('Y'))
                                  ->first();

        // Recent payment history
        $recentPayments = RentRecord::where('tenant_id', $tenant->id)
                                     ->whereIn('status', ['paid', 'partial'])
                                     ->latest()
                                     ->take(5)
                                     ->get();

        // Outstanding balance across all records
        $totalOutstanding = RentRecord::where('tenant_id', $tenant->id)
                                       ->whereIn('status', ['pending', 'partial', 'overdue'])
                                       ->get()
                                       ->sum(fn($r) => $r->balance());

        return view('tenant.dashboard', compact(
            'tenant',
            'currentRent',
            'recentPayments',
            'totalOutstanding'
        ));
    }

    public function paymentHistory()
    {
        $tenant = Tenant::where('user_id', auth()->id())->firstOrFail();

        $payments = RentRecord::where('tenant_id', $tenant->id)
                               ->latest()
                               ->get();

        return view('tenant.payments', compact('tenant', 'payments'));
    }

    public function profile()
    {
        $tenant = Tenant::where('user_id', auth()->id())
                        ->with(['property', 'unit'])
                        ->first();

        return view('tenant.profile', compact('tenant'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . auth()->id(),
        ]);

        auth()->user()->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('current_password')) {
            $request->validate([
                'current_password'      => 'required',
                'new_password'          => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($request->current_password, auth()->user()->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.']);
            }

            auth()->user()->update([
                'password' => Hash::make($request->new_password),
            ]);
        }

        return back()->with('success', 'Profile updated successfully.');
    }
}

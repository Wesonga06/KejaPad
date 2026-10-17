<?php

namespace App\Http\Controllers;

use App\Models\User;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalLandlords = User::where('role', 'landlord')->count();
        $totalCaretakers = User::where('role', 'caretaker')->count();
        $totalTenants = User::where('role', 'tenant')->count();
        $pendingLandlords = User::where('role', 'landlord')
            ->where('status', User::STATUS_PENDING)
            ->count();

        return view('super_admin.dashboard', compact(
            'totalUsers',
            'totalLandlords',
            'totalCaretakers',
            'totalTenants',
            'pendingLandlords'
        ));
    }
}

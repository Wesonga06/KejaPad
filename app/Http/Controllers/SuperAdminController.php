<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalLandlords = User::where('role', 'landlord')->count();
        $totalCaretakers = User::where('role', 'caretaker')->count();
        $totalTenants = User::where('role', 'tenant')->count();

        return view('super_admin.dashboard', compact(
            'totalUsers',
            'totalLandlords',
            'totalCaretakers',
            'totalTenants'
        ));
    }
}

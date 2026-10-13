<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // List all users
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::latest()->paginate(20);

        return view('super_admin.users.index', compact('users'));
    }

    // Show create form
    public function create()
    {
        $this->authorize('create', User::class);

        return view('super_admin.users.create');
    }

    // Store new user
    public function store(StoreUserRequest $request)
    {
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        return redirect()->route('super_admin.users.index')
            ->with('success', 'User created successfully.');
    }

    // Show edit form
    public function edit(User $user)
    {
        $this->authorize('update', $user);

        return view('super_admin.users.edit', compact('user'));
    }

    // Update user
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('super_admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    // Toggle active/inactive
    public function toggleStatus(User $user)
    {
        $this->authorize('update', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }

    // Delete user
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}

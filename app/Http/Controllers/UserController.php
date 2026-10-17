<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // List all users (filterable)
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $filters = [
            'q' => (string) $request->query('q', ''),
            'role' => (string) $request->query('role', ''),
            'status' => (string) $request->query('status', ''),
        ];

        $users = User::query()
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.$filters['q'].'%';
                $query->where(fn ($q) => $q
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like));
            })
            ->when($filters['role'] !== '', fn ($query) => $query->where('role', $filters['role']))
            ->when($filters['status'] === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roleCounts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $total = User::count();

        return view('super_admin.users.index', compact('users', 'filters', 'roleCounts', 'total'));
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
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone ?: null,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => true,
        ]);

        $this->audit()->record(
            'user.created',
            "Created user {$user->email} ({$user->role})",
            [], ['id' => $user->id, 'email' => $user->email, 'role' => $user->role],
            $user
        );

        return redirect()->route('super_admin.users.index')
            ->with('success', 'User created successfully.');
    }

    // User detail
    public function show(User $user)
    {
        $this->authorize('view', $user);

        $activity = AuditLog::query()
            ->where(fn ($query) => $query
                ->where('user_id', $user->id)
                ->orWhere(fn ($nested) => $nested
                    ->where('auditable_type', $user->getMorphClass())
                    ->where('auditable_id', $user->id)))
            ->latest()
            ->paginate(15);

        $related = [
            'properties' => $user->properties()->count(),
            'caretaker_properties' => $user->caretakerProperties()->count(),
            'tenant_profile' => $user->tenantProfile,
            'landlord_approval' => $user->landlordApprovalActions()->latest()->first(),
        ];

        return view('super_admin.users.show', compact('user', 'activity', 'related'));
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
            'phone' => $request->phone ?: null,
            'role' => $request->role,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        if ($request->has('is_active') && $user->id !== auth()->id()) {
            $user->update(['is_active' => (bool) $request->boolean('is_active')]);
        }

        $this->audit()->record(
            'user.updated',
            "Updated user {$user->email}",
            [], ['role' => $user->role, 'is_active' => (bool) $user->is_active],
            $user
        );

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

        if ($user->role === 'super_admin' && $this->otherSuperAdmins()->isEmpty()) {
            return back()->with('error', 'You cannot deactivate the last active super administrator.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        $this->audit()->record(
            'user.status_changed',
            "User {$user->email} {$status}",
            ['is_active' => ! $user->is_active], ['is_active' => (bool) $user->is_active],
            $user
        );

        return back()->with('success', "User {$status} successfully.");
    }

    // Delete user
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->role === 'super_admin' && $this->otherSuperAdmins()->isEmpty()) {
            return back()->with('error', 'You cannot delete the last active super administrator.');
        }

        $email = $user->email;
        $user->delete();

        $this->audit()->record(
            'user.deleted',
            "Deleted user {$email}",
            ['email' => $email], []
        );

        return back()->with('success', 'User deleted successfully.');
    }

    // Remaining active super administrators besides the current actor.
    protected function otherSuperAdmins()
    {
        return User::where('role', 'super_admin')
            ->where('is_active', true)
            ->whereKeyNot(auth()->id())
            ->get();
    }

    protected function audit(): AuditService
    {
        return app(AuditService::class);
    }
}

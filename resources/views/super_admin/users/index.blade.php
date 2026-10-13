@extends('layouts.app')

@section('page-title', 'User Management')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('super_admin.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('super_admin.users.index') }}" class="nav-link active">
        <i class="bi bi-people"></i> Users
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-gear"></i> System Settings
    </a>

    <div class="nav-section-title">Reports</div>
    <a href="#" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">All Users</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">Manage all system users and their roles.</p>
        </div>
        <a href="{{ route('super_admin.users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> Add User
        </a>
    </div>

    <div class="stat-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $user)
                    <tr>
                        <td>{{ $users->firstItem() + $index }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:34px; height:34px; background:linear-gradient(135deg,#4cc9f0,#0f3460); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:0.8rem;">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <span>{{ $user->name }}</span>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge rounded-pill
                                @if($user->role === 'super_admin') bg-danger
                                @elseif($user->role === 'landlord') bg-primary
                                @elseif($user->role === 'caretaker') bg-success
                                @else bg-info text-dark
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $user->created_at->format('d M Y') }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('super_admin.users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form method="POST" action="{{ route('super_admin.users.toggle-status', $user) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="bi {{ $user->is_active ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('super_admin.users.destroy', $user) }}"
                                      onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($users->hasPages())
        <div class="mt-3">{{ $users->links() }}</div>
    @endif
@endsection
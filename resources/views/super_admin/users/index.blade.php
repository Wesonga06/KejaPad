@extends('layouts.app')

@section('page-title', 'User Management')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Users</h1>
            <p class="kp-page-subtitle">Manage every account, role and access state across the platform.</p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('super_admin.users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i> Add User
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon purple"><i class="bi bi-people"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($total) }}</div>
                <p class="kp-kpi-label">Total users</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon green"><i class="bi bi-buildings"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($roleCounts['landlord'] ?? 0) }}</div>
                <p class="kp-kpi-label">Landlords</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon amber"><i class="bi bi-person-badge"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($roleCounts['caretaker'] ?? 0) }}</div>
                <p class="kp-kpi-label">Caretakers</p>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card kp-kpi h-100">
                <div class="kp-kpi-top"><div class="kp-kpi-icon gold"><i class="bi bi-person"></i></div></div>
                <div class="kp-kpi-value">{{ number_format($roleCounts['tenant'] ?? 0) }}</div>
                <p class="kp-kpi-label">Tenants</p>
            </div>
        </div>
    </div>

    <div class="kp-card mb-4">
        <form method="GET" action="{{ route('super_admin.users.index') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label fw-semibold">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control"
                       placeholder="Name, email or phone">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Role</label>
                <select name="role" class="form-select">
                    <option value="">All roles</option>
                    @foreach(['super_admin' => 'Super Admin', 'landlord' => 'Landlord', 'caretaker' => 'Caretaker', 'tenant' => 'Tenant'] as $value => $label)
                        <option value="{{ $value }}" {{ $filters['role'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Access</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if($filters['q'] !== '' || $filters['role'] !== '' || $filters['status'] !== '')
                    <a href="{{ route('super_admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="kp-card">
        @if($users->isEmpty())
            <div class="kp-empty">
                <i class="bi bi-people"></i>
                <p class="mb-0">No users match your filters.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Access</th>
                            <th>Joined</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $index => $user)
                            <tr>
                                <td>{{ $users->firstItem() + $index }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width:34px; height:34px; background:linear-gradient(135deg,#F4C542,#5926C8); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:600; font-size:0.8rem;">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <a href="{{ route('super_admin.users.show', $user) }}" class="fw-semibold">{{ $user->name }}</a>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $user->email }}</div>
                                    <small class="text-muted">{{ $user->phone ?: '—' }}</small>
                                </td>
                                <td>
                                    @php
                                        $roleBadge = match($user->role) {
                                            'super_admin' => 'kp-badge-purple',
                                            'landlord' => 'kp-badge-gold',
                                            'caretaker' => 'kp-badge-success',
                                            default => 'kp-badge-muted',
                                        };
                                    @endphp
                                    <span class="kp-badge {{ $roleBadge }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                                </td>
                                <td>
                                    <span class="kp-badge {{ $user->statusBadgeClass() }}">{{ $user->statusLabel() }}</span>
                                </td>
                                <td>
                                    <span class="kp-badge {{ $user->activeBadgeClass() }}">{{ $user->activeLabel() }}</span>
                                </td>
                                <td>{{ $user->created_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('super_admin.users.show', $user) }}"
                                           class="btn btn-sm btn-outline-secondary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('super_admin.users.edit', $user) }}"
                                           class="btn btn-sm btn-outline-primary" title="Edit">
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
                                              onsubmit="return confirm('Delete this user? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="mt-3">{{ $users->links() }}</div>
            @endif
        @endif
    </div>
@endsection

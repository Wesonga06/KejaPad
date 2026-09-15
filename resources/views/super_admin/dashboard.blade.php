@extends('layouts.app')

@section('page-title', 'Super Admin Dashboard')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('super_admin.dashboard') }}" class="nav-link active">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('super_admin.users.index') }}" class="nav-link">
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
    <!-- Welcome Banner -->
    <div class="p-4 mb-4 rounded-3" style="background: linear-gradient(135deg, #1a1a2e, #0f3460); color: white;">
        <h4 class="fw-bold mb-1">Welcome back, {{ auth()->user()->name }} 👋</h4>
        <p class="mb-0 opacity-75">Here's what's happening on KejaPad today.</p>
    </div>

    <!-- Stat Cards -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total Users</p>
                        <h3 class="stat-value">{{ $totalUsers }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#e8f4fd;">
                        <i class="bi bi-people" style="color:#0f3460;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Landlords</p>
                        <h3 class="stat-value">{{ $totalLandlords }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#e8f0fe;">
                        <i class="bi bi-person-badge" style="color:#0f3460;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Caretakers</p>
                        <h3 class="stat-value">{{ $totalCaretakers }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#e6f9f0;">
                        <i class="bi bi-person-gear" style="color:#198754;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Tenants</p>
                        <h3 class="stat-value">{{ $totalTenants }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#fff3e0;">
                        <i class="bi bi-house-heart" style="color:#fd7e14;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users Table -->
    <div class="stat-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0">Recent Users</h6>
            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\User::latest()->take(5)->get() as $index => $user)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar" style="width:32px; height:32px; font-size:0.75rem; background: linear-gradient(135deg, #4cc9f0, #0f3460); border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-weight:600;">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                {{ $user->name }}
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
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
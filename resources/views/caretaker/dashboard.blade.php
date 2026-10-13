@extends('layouts.app')

@section('page-title', 'Caretaker Dashboard')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('caretaker.dashboard') }}" class="nav-link active">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="{{ route('caretaker.announcements.index') }}" class="nav-link">
    <i class="bi bi-megaphone"></i> Announcements
    </a>

    <div class="nav-section-title">Tasks</div>
    <a href="{{ route('caretaker.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance Requests
    </a>
@endsection

@section('content')
    <div class="p-4 mb-4 rounded-3"
         style="background: linear-gradient(135deg, #198754, #d4af37); color: white;">
        <h4 class="fw-bold mb-1">Welcome, {{ auth()->user()->name }} 👋</h4>
        <p class="mb-0 opacity-75">Here are the maintenance requests for your properties.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Pending</p>
                        <h3 class="stat-value">{{ $pendingCount }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#f0f0f0;">
                        <i class="bi bi-hourglass-split" style="color:#6c757d;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">In Progress</p>
                        <h3 class="stat-value">{{ $inProgressCount }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#fff3e0;">
                        <i class="bi bi-tools" style="color:#fd7e14;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Resolved</p>
                        <h3 class="stat-value">{{ $resolvedCount }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#e6f9f0;">
                        <i class="bi bi-check-circle" style="color:#198754;"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total</p>
                        <h3 class="stat-value">{{ $totalCount }}</h3>
                    </div>
                    <div class="stat-icon" style="background:#efe7fb;">
                        <i class="bi bi-clipboard-check" style="color:#4c1d95;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="stat-card text-center py-4">
        <a href="{{ route('caretaker.maintenance.index') }}" class="btn btn-primary">
            <i class="bi bi-tools me-2"></i> View My Tasks
        </a>
    </div>
@endsection
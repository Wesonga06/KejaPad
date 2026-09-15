@extends('layouts.app')

@section('page-title', 'Announcements')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('tenant.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">My Account</div>
    <a href="{{ route('tenant.payments') }}" class="nav-link">
        <i class="bi bi-receipt"></i> Payment History
    </a>
    <a href="{{ route('tenant.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('tenant.announcements.index') }}" class="nav-link active">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="{{ route('tenant.profile') }}" class="nav-link">
        <i class="bi bi-person-circle"></i> My Profile
    </a>
@endsection

@section('content')
    <div class="mb-4">
        <h5 class="fw-bold mb-1">Announcements</h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Notices and updates from your landlord.
        </p>
    </div>

    @if($announcements->isEmpty())
        <div class="stat-card text-center py-5">
            <i class="bi bi-megaphone"
               style="font-size:3rem; color:#dee2e6;"></i>
            <p class="mt-3 text-muted">No announcements at this time.</p>
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach($announcements as $ann)
            <div class="stat-card">
                <div class="d-flex align-items-start gap-3">
                    <div style="width:44px; height:44px; border-radius:10px; flex-shrink:0;
                                display:flex; align-items:center; justify-content:center;
                                background:#e8f0fe;">
                        <i class="bi {{ $ann->categoryIcon() }}"
                           style="font-size:1.2rem; color:#0f3460;"></i>
                    </div>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="fw-bold mb-0">{{ $ann->title }}</h6>
                            <span class="badge bg-{{ $ann->categoryColor() }} rounded-pill"
                                  style="font-size:0.72rem;">
                                {{ ucfirst($ann->category) }}
                            </span>
                        </div>
                        @if($ann->property)
                            <small class="text-muted d-block mb-2">
                                <i class="bi bi-buildings me-1"></i>
                                {{ $ann->property->name }}
                            </small>
                        @endif
                        <p class="mb-2" style="font-size:0.9rem;">
                            {{ $ann->body }}
                        </p>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            {{ $ann->created_at->diffForHumans() }}
                            — {{ $ann->created_at->format('d M Y') }}
                        </small>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
@endsection
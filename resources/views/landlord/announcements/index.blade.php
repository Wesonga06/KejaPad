@extends('layouts.app')

@section('page-title', 'Announcements')

@section('sidebar-links')
    <div class="nav-section-title">Main</div>
    <a href="{{ route('landlord.dashboard') }}" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section-title">Management</div>
    <a href="{{ route('landlord.properties.index') }}" class="nav-link">
        <i class="bi bi-buildings"></i> Properties
    </a>
    <a href="{{ route('landlord.tenants.index') }}" class="nav-link">
        <i class="bi bi-people"></i> Tenants
    </a>
    <a href="{{ route('landlord.rent.index') }}" class="nav-link">
        <i class="bi bi-cash-coin"></i> Rent
    </a>

    <div class="nav-section-title">Other</div>
    <a href="{{ route('landlord.maintenance.index') }}" class="nav-link">
        <i class="bi bi-tools"></i> Maintenance
    </a>
    <a href="{{ route('landlord.announcements.index') }}" class="nav-link active">
        <i class="bi bi-megaphone"></i> Announcements
    </a>
    <a href="#" class="nav-link">
        <i class="bi bi-bar-chart"></i> Reports
    </a>
@endsection

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h5 class="fw-bold mb-1">Announcements</h5>
            <p class="text-muted mb-0" style="font-size:0.85rem;">
                Post notices and updates to your tenants and caretakers.
            </p>
        </div>
        <a href="{{ route('landlord.announcements.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i> New Announcement
        </a>
    </div>

    @if($announcements->isEmpty())
        <div class="stat-card text-center py-5">
            <i class="bi bi-megaphone" style="font-size:3rem; color:#dee2e6;"></i>
            <p class="mt-3 text-muted">No announcements yet.</p>
            <a href="{{ route('landlord.announcements.create') }}"
               class="btn btn-primary mt-2">
                <i class="bi bi-plus-circle me-2"></i> Post Announcement
            </a>
        </div>
    @else
        <div class="row g-4">
            @foreach($announcements as $ann)
            <div class="col-12 col-md-6">
                <div class="stat-card h-100">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="stat-icon"
                                 style="width:38px; height:38px; border-radius:8px;
                                        background: var(--bs-{{ $ann->categoryColor() }}-bg-subtle, #e8f0fe);">
                                <i class="bi {{ $ann->categoryIcon() }}"
                                   style="color: var(--bs-{{ $ann->categoryColor() }}, #0f3460); font-size:1rem;"></i>
                            </div>
                            <div>
                                <span class="badge bg-{{ $ann->categoryColor() }} rounded-pill"
                                      style="font-size:0.72rem;">
                                    {{ ucfirst($ann->category) }}
                                </span>
                                <span class="badge bg-light text-dark border rounded-pill ms-1"
                                      style="font-size:0.72rem;">
                                    {{ ucfirst($ann->audience) }}
                                </span>
                            </div>
                        </div>
                        <span class="badge {{ $ann->is_published ? 'bg-success' : 'bg-secondary' }}">
                            {{ $ann->is_published ? 'Published' : 'Draft' }}
                        </span>
                    </div>

                    <h6 class="fw-bold mb-1">{{ $ann->title }}</h6>

                    @if($ann->property)
                        <small class="text-muted d-block mb-2">
                            <i class="bi bi-buildings me-1"></i>
                            {{ $ann->property->name }}
                        </small>
                    @else
                        <small class="text-muted d-block mb-2">
                            <i class="bi bi-globe me-1"></i> All Properties
                        </small>
                    @endif

                    <p class="text-muted mb-3" style="font-size:0.85rem;">
                        {{ Str::limit($ann->body, 120) }}
                    </p>

                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
                        <small class="text-muted">
                            {{ $ann->created_at->format('d M Y') }}
                        </small>
                        <div class="d-flex gap-2">
                            <form method="POST"
                                  action="{{ route('landlord.announcements.toggle', $ann) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        class="btn btn-sm {{ $ann->is_published ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        title="{{ $ann->is_published ? 'Unpublish' : 'Publish' }}">
                                    <i class="bi {{ $ann->is_published ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                </button>
                            </form>
                            <a href="{{ route('landlord.announcements.edit', $ann) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST"
                                  action="{{ route('landlord.announcements.destroy', $ann) }}"
                                  onsubmit="return confirm('Delete this announcement?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    @endif
@endsection
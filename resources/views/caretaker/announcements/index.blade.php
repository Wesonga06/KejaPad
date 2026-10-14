@extends('layouts.app')

@section('page-title', 'Announcements')


@section('content')
    <div class="mb-4">
        <h5 class="fw-bold mb-1">Announcements</h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Notices and updates from landlords.
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
                                background:rgba(22,134,83,0.12);">
                        <i class="bi {{ $ann->categoryIcon() }}"
                           style="font-size:1.2rem; color:#168653;"></i>
                    </div>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h6 class="fw-bold mb-0">{{ $ann->title }}</h6>
                            <span class="badge bg-{{ $ann->categoryColor() }} rounded-pill"
                                  style="font-size:0.72rem;">
                                {{ ucfirst($ann->category) }}
                            </span>
                        </div>
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

    @if($announcements->hasPages())
        <div class="mt-3">{{ $announcements->links() }}</div>
    @endif
@endsection
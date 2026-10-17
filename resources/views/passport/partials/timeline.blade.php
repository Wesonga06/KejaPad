@php($compact = $compact ?? false)

@if($events->isEmpty())
    <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>No activity has been recorded for this unit yet.</p>
@else
    <div class="kp-timeline">
        @foreach($events as $event)
            <div class="kp-timeline-item d-flex gap-3 {{ $compact ? 'pb-3' : 'pb-4' }}">
                <div class="kp-timeline-marker">
                    <i class="bi {{ $event['icon'] ?? 'bi-dot' }}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold {{ $compact ? 'small' : '' }}">
                                @if(! empty($event['url']))
                                    <a href="{{ $event['url'] }}" class="text-decoration-none">{{ $event['title'] }}</a>
                                @else
                                    {{ $event['title'] }}
                                @endif
                            </div>
                            @if(! empty($event['description']))
                                <div class="text-muted {{ $compact ? 'small' : '' }}" style="font-size:{{ $compact ? '0.78rem' : '0.85rem' }};">
                                    {{ $event['description'] }}
                                </div>
                            @endif
                            @if(! $compact && ! empty($event['actor']))
                                <div class="text-muted small mt-1"><i class="bi bi-person me-1"></i>{{ $event['actor'] }}</div>
                            @endif
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="text-muted small">{{ $event['date']->format('d M Y') }}</div>
                            @if(! empty($event['status']))
                                <span class="kp-badge kp-badge-muted mt-1">{{ $event['status'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@once
    @push('styles')
        <style>
            .kp-timeline-item { position: relative; }
            .kp-timeline-item:not(:last-child)::before {
                content: '';
                position: absolute;
                left: 15px;
                top: 32px;
                bottom: 0;
                width: 2px;
                background: var(--kp-border);
            }
            .kp-timeline-marker {
                flex: 0 0 auto;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--kp-royal);
                color: #fff;
                font-size: 0.85rem;
                z-index: 1;
            }
        </style>
    @endpush
@endonce

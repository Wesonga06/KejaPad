@extends('layouts.app')

@section('page-title', 'Move-out Inspection')

@section('content')
    @php
        $request = $inspection->moveOutRequest;
        $tenant = $request->tenant;
        $checklist = \App\Models\MoveOutInspection::checklist();
        $conditions = \App\Models\MoveOutInspection::conditions();
    @endphp

    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Inspection — {{ $tenant->full_name ?? '—' }}</h1>
            <p class="kp-page-subtitle">
                {{ $request->property->name ?? '—' }} / {{ $request->unit->unit_number ?? '—' }}
                &middot; <span class="kp-badge {{ $inspection->statusBadgeClass() }}">{{ $inspection->statusLabel() }}</span>
            </p>
        </div>
        <div class="kp-page-actions">
            <a href="{{ route('caretaker.inspections.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    @if($inspection->isSubmitted())
        <div class="alert alert-success" style="font-size:0.88rem;">
            <i class="bi bi-check-circle me-1"></i> This inspection was submitted on
            {{ optional($inspection->completed_at)->format('d M Y H:i') }}.
            Recommendation: <strong>{{ $inspection->recommendationLabel() }}</strong>.
        </div>
    @endif

    <form method="POST" action="{{ route('caretaker.inspections.submit', $inspection) }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="kp-card">
                    <div class="kp-card-header"><h2 class="kp-card-title">Checklist</h2></div>

                    @foreach($checklist as $category => $group)
                        <div class="mb-4">
                            <h3 class="kp-section-title mb-2">{{ $group['label'] }}</h3>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr><th style="width:35%;">Item</th><th style="width:30%;">Condition</th><th>Notes</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($inspection->items->where('category', $category) as $item)
                                            <tr>
                                                <td>{{ $item->label }}</td>
                                                <td>
                                                    @if($inspection->isSubmitted())
                                                        {{ $item->conditionLabel() }}
                                                    @else
                                                        <select name="items[{{ $item->id }}][condition]" class="form-select form-select-sm">
                                                            <option value="">Select...</option>
                                                            @foreach($conditions as $value => $label)
                                                                <option value="{{ $value }}" @selected($item->condition === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($inspection->isSubmitted())
                                                        {{ $item->comment ?: '—' }}
                                                    @else
                                                        <input type="text" name="items[{{ $item->id }}][comment]"
                                                               class="form-control form-control-sm" value="{{ $item->comment }}" maxlength="500">
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="kp-card mb-4">
                    <div class="kp-card-header"><h2 class="kp-card-title">Utilities &amp; keys</h2></div>
                    <div class="mb-3">
                        <label class="form-label" for="electricity_meter">Electricity meter reading</label>
                        <input type="text" name="electricity_meter" id="electricity_meter" class="form-control"
                               value="{{ old('electricity_meter', $inspection->electricity_meter) }}" @disabled($inspection->isSubmitted())>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="water_meter">Water meter reading</label>
                        <input type="text" name="water_meter" id="water_meter" class="form-control"
                               value="{{ old('water_meter', $inspection->water_meter) }}" @disabled($inspection->isSubmitted())>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cleanliness">General cleanliness</label>
                        <select name="cleanliness" id="cleanliness" class="form-select" @disabled($inspection->isSubmitted())>
                            <option value="">Select...</option>
                            @foreach(['clean' => 'Clean', 'average' => 'Average', 'dirty' => 'Dirty'] as $value => $label)
                                <option value="{{ $value }}" @selected($inspection->cleanliness === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="keys_returned" id="keys_returned" value="1" class="form-check-input"
                               @checked($inspection->keys_returned) @disabled($inspection->isSubmitted())>
                        <label class="form-check-label" for="keys_returned">Keys returned</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="keys_notes">Key notes</label>
                        <input type="text" name="keys_notes" id="keys_notes" class="form-control" maxlength="1000"
                               value="{{ old('keys_notes', $inspection->keys_notes) }}" @disabled($inspection->isSubmitted())>
                    </div>
                </div>

                <div class="kp-card">
                    <div class="kp-card-header"><h2 class="kp-card-title">Findings</h2></div>
                    <div class="mb-3">
                        <label class="form-label" for="general_notes">General notes</label>
                        <textarea name="general_notes" id="general_notes" rows="3" class="form-control"
                                  @disabled($inspection->isSubmitted())>{{ old('general_notes', $inspection->general_notes) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="photos">Photos</label>
                        @if(! $inspection->isSubmitted())
                            <input type="file" name="photos[]" id="photos" class="form-control" accept="image/*" multiple>
                            <div class="form-text">Up to 6 images (JPG, PNG, WEBP), 5&nbsp;MB each.</div>
                            @error('photos')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            @error('photos.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @endif
                        @if(! empty($inspection->photoUrls()))
                            <div class="d-flex flex-wrap gap-2 {{ $inspection->isSubmitted() ? '' : 'mt-2' }}">
                                @foreach($inspection->photoUrls() as $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener">
                                        <img src="{{ $url }}" alt="Inspection photo" class="rounded"
                                             style="width:72px;height:72px;object-fit:cover;">
                                    </a>
                                @endforeach
                            </div>
                        @elseif($inspection->isSubmitted())
                            <p class="text-muted small mb-0">No photos were attached.</p>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="recommendation">Recommendation</label>
                        <select name="recommendation" id="recommendation" class="form-select" @disabled($inspection->isSubmitted())>
                            <option value="recommend_clearance" @selected($inspection->recommendation === 'recommend_clearance')>Recommend clearance</option>
                            <option value="further_action" @selected($inspection->recommendation === 'further_action')>Further action required</option>
                        </select>
                        @error('recommendation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    @if(! $inspection->isSubmitted())
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-send me-2"></i> Submit Inspection
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </form>
@endsection

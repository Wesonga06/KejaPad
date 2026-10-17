@extends('layouts.app')

@section('page-title', $inspection->typeLabel() . ' — ' . $unit->unit_number)

@section('content')
@php
    $isCaretaker = $mode === 'caretaker';
    $checklist = \App\Models\UnitInspection::checklist();
    $itemsByCategory = $inspection->itemsByCategory();
    $backUrl = $isCaretaker
        ? route('caretaker.passport.show', [$unit, 'tab' => 'inspections'])
        : route('landlord.passport.show', [$unit->property_id, $unit, 'tab' => 'inspections']);
    $draftUrl = $isCaretaker
        ? route('caretaker.passport.inspections.draft', $inspection)
        : route('landlord.passport.inspections.draft', $inspection);
    $submitUrl = $isCaretaker
        ? route('caretaker.passport.inspections.submit', $inspection)
        : route('landlord.passport.inspections.submit', $inspection);
    $submitted = $inspection->isSubmitted();
@endphp

<div class="kp-page-header">
    <div>
        <h1 class="kp-page-title">{{ $inspection->typeLabel() }}</h1>
        <p class="kp-page-subtitle">
            {{ $unit->unit_number }} &middot; {{ $unit->property->name }}
            &middot; <span class="kp-badge {{ $inspection->statusBadgeClass() }}">{{ $inspection->statusLabel() }}</span>
            @if($inspection->isAcknowledged())
                &middot; <span class="kp-badge kp-badge-purple">Acknowledged</span>
            @endif
        </p>
    </div>
    <div class="kp-page-actions">
        <a href="{{ $backUrl }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i> Back to Passport</a>
    </div>
</div>

@if($submitted)
    <div class="alert alert-success d-flex align-items-center">
        <i class="bi bi-check2-circle me-2"></i>
        This inspection has been submitted. Submitted inspections form the factual record and are not overwritten; create a new inspection to record later changes.
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i> Please correct the highlighted fields and try again.
    </div>
@endif

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="kp-card mb-4">
            <div class="kp-card-header">
                <h2 class="kp-card-title">Condition checklist</h2>
                <span class="kp-card-subtitle">One condition per item. Add a comment only where it helps.</span>
            </div>
            @foreach($itemsByCategory as $category => $items)
                <div class="mb-4">
                    <h3 class="h6 fw-semibold mb-2">{{ $checklist[$category]['label'] ?? ucfirst($category) }}</h3>
                    @foreach($items as $item)
                        <div class="row g-2 align-items-start border-bottom py-2">
                            <div class="col-md-4 pt-1">
                                <span class="fw-medium">{{ $item->label }}</span>
                            </div>
                            <div class="col-md-3">
                                <select name="items[{{ $item->id }}][condition]" form="inspection-form" class="form-select form-select-sm">
                                    <option value="">Not recorded</option>
                                    @foreach($conditions as $value => $label)
                                        <option value="{{ $value }}" @selected($item->condition === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <input type="text" name="items[{{ $item->id }}][comment]" form="inspection-form"
                                       class="form-control form-control-sm" placeholder="Comment (optional)"
                                       value="{{ $item->comment }}">
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="kp-card">
            <div class="kp-card-header"><h2 class="kp-card-title">Inspection details</h2></div>
            <form id="inspection-form" method="POST" action="{{ $draftUrl }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="inspection_date">Inspection date</label>
                    <input type="date" name="inspection_date" id="inspection_date" class="form-control"
                           value="{{ optional($inspection->inspection_date)->toDateString() ?? now()->toDateString() }}">
                    @error('inspection_date')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="cleanliness">General cleanliness</label>
                    <select name="cleanliness" id="cleanliness" class="form-select">
                        <option value="">Not recorded</option>
                        @foreach(['clean' => 'Clean', 'average' => 'Average', 'dirty' => 'Dirty'] as $value => $label)
                            <option value="{{ $value }}" @selected($inspection->cleanliness === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label" for="electricity_meter">Electricity meter</label>
                        <input type="text" name="electricity_meter" id="electricity_meter" class="form-control"
                               value="{{ $inspection->electricity_meter }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="water_meter">Water meter</label>
                        <input type="text" name="water_meter" id="water_meter" class="form-control"
                               value="{{ $inspection->water_meter }}">
                    </div>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="keys_returned" value="1" id="keys_returned"
                           @checked($inspection->keys_returned)>
                    <label class="form-check-label" for="keys_returned">Keys returned</label>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="keys_notes">Keys notes</label>
                    <input type="text" name="keys_notes" id="keys_notes" class="form-control" value="{{ $inspection->keys_notes }}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="recommendation">Recommendation</label>
                    <select name="recommendation" id="recommendation" class="form-select">
                        <option value="">No recommendation</option>
                        <option value="{{ \App\Models\UnitInspection::RECOMMEND_CLEARANCE }}" @selected($inspection->recommendation === \App\Models\UnitInspection::RECOMMEND_CLEARANCE)>Recommend clearance</option>
                        <option value="{{ \App\Models\UnitInspection::RECOMMEND_FURTHER_ACTION }}" @selected($inspection->recommendation === \App\Models\UnitInspection::RECOMMEND_FURTHER_ACTION)>Further action required</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="general_notes">General notes</label>
                    <textarea name="general_notes" id="general_notes" rows="3" class="form-control">{{ $inspection->general_notes }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="photos">Add photographs <span class="text-muted fw-normal">(max 8, images)</span></label>
                    <input type="file" name="photos[]" id="photos" class="form-control" accept="image/*" multiple>
                    @error('photos')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                @if(! empty($inspection->photoUrls()))
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($inspection->photoUrls() as $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener">
                                <img src="{{ $url }}" alt="Inspection photo" class="rounded" style="width:64px;height:64px;object-fit:cover;">
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save me-1"></i> Save Draft</button>
                    @if(! $submitted)
                        <button type="submit" formaction="{{ $submitUrl }}" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Submit Inspection
                        </button>
                    @endif
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.78rem;">
                    Saving keeps the inspection as a draft. Submitting locks it into the unit's permanent history.
                </p>
            </form>
        </div>
    </div>
</div>
@endsection

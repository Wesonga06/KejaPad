@extends('layouts.app')

@section('page-title', 'Alert Settings')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">Early-Warning Settings</h1>
            <p class="kp-page-subtitle">Fine-tune the warning thresholds for <strong>{{ $property->name }}</strong>. Leave a field blank to use the platform default.</p>
        </div>
        <a href="{{ route('landlord.properties.edit', $property) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i> Back to property
        </a>
    </div>

    <div class="stat-card" style="max-width:720px;">
        <form method="POST" action="{{ route('landlord.properties.alerts.settings.update', $property) }}">
            @csrf

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Warning threshold</th>
                            <th style="width:160px;">Your value</th>
                            <th style="width:120px;">Default</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($keys as $key)
                            @php
                                $field = str_replace('.', '_', $key);
                                $current = $overrides[$key] ?? null;
                            @endphp
                            <tr>
                                <td style="font-size:0.85rem;">{{ $labels[$key] }}</td>
                                <td>
                                    <input type="number" min="0" name="{{ $field }}"
                                           class="form-control form-control-sm @error($field) is-invalid @enderror"
                                           value="{{ old($field, $current) }}"
                                           placeholder="{{ $defaults[$key] }}">
                                    @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </td>
                                <td class="text-muted" style="font-size:0.85rem;">{{ $defaults[$key] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i> Save settings
                </button>
                <button type="submit" name="reset" value="1" class="btn btn-outline-secondary"
                        onclick="return confirm('Reset all thresholds for this property to the platform defaults?')">
                    Reset to defaults
                </button>
            </div>
        </form>
    </div>

    <p class="text-muted mt-3" style="font-size:0.82rem;">
        <i class="bi bi-info-circle me-1"></i>
        "0" disables a rule where noted (e.g. inspection interval). These settings only apply to {{ $property->name }}.
    </p>
@endsection

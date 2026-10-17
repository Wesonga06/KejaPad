<?php

namespace App\Http\Requests\Property;

use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyAlertSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $property = $this->route('property');

        return $property instanceof Property
            && $this->user()->can('update', $property);
    }

    public function rules(): array
    {
        $rules = [
            'reset' => 'nullable|boolean',
        ];

        // Every overridable threshold is an optional non-negative integer.
        foreach (config('alerts.overridable', []) as $key) {
            $rules[$this->fieldName($key)] = 'nullable|integer|min:0|max:3650';
        }

        return $rules;
    }

    public function fieldName(string $key): string
    {
        return str_replace('.', '_', $key);
    }
}

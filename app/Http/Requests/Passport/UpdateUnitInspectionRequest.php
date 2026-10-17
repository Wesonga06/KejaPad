<?php

namespace App\Http\Requests\Passport;

use App\Models\MoveOutInspection;
use App\Models\UnitInspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('inspection'));
    }

    public function rules(): array
    {
        return [
            'inspection_date' => 'nullable|date',
            'keys_returned' => 'nullable|boolean',
            'keys_notes' => 'nullable|string|max:1000',
            'electricity_meter' => 'nullable|string|max:100',
            'water_meter' => 'nullable|string|max:100',
            'cleanliness' => ['nullable', Rule::in(['clean', 'average', 'dirty'])],
            'general_notes' => 'nullable|string|max:2000',
            'recommendation' => ['nullable', Rule::in([
                UnitInspection::RECOMMEND_CLEARANCE,
                UnitInspection::RECOMMEND_FURTHER_ACTION,
            ])],
            'photos' => 'nullable|array|max:8',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'items' => 'nullable|array',
            'items.*.condition' => ['nullable', Rule::in(array_keys(MoveOutInspection::conditions()))],
            'items.*.comment' => 'nullable|string|max:500',
        ];
    }
}

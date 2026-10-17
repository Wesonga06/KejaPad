<?php

namespace App\Http\Requests\MoveOut;

use App\Models\MoveOutInspection;
use App\Models\MoveOutInspectionItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submit', $this->route('inspection'));
    }

    public function rules(): array
    {
        $conditions = array_keys(MoveOutInspection::conditions());
        $natures = array_keys(MoveOutInspectionItem::natures());

        return [
            'keys_returned' => 'nullable|boolean',
            'keys_notes' => 'nullable|string|max:1000',
            'electricity_meter' => 'nullable|string|max:100',
            'water_meter' => 'nullable|string|max:100',
            'cleanliness' => ['nullable', Rule::in(['clean', 'average', 'dirty'])],
            'general_notes' => 'nullable|string|max:2000',
            'photos' => 'nullable|array|max:6',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'recommendation' => ['required', Rule::in(['recommend_clearance', 'further_action'])],
            'items' => 'nullable|array',
            'items.*.condition' => ['nullable', Rule::in($conditions)],
            'items.*.nature' => ['nullable', Rule::in($natures)],
            'items.*.comment' => 'nullable|string|max:500',
        ];
    }
}

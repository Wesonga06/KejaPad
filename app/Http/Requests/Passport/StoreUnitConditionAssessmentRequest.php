<?php

namespace App\Http\Requests\Passport;

use App\Models\UnitConditionAssessment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitConditionAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assessCondition', $this->route('unit'));
    }

    public function rules(): array
    {
        return [
            'assessed_at' => 'nullable|date',
            'rating' => ['nullable', Rule::in(array_keys(UnitConditionAssessment::ratings()))],
            'summary' => 'required|string|max:2000',
        ];
    }
}

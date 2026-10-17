<?php

namespace App\Http\Requests\Passport;

use App\Models\UnitInspection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('managePassport', $this->route('unit'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([UnitInspection::TYPE_MOVE_IN, UnitInspection::TYPE_ROUTINE])],
            'inspection_date' => 'nullable|date',
            'tenant_id' => 'nullable|integer|exists:tenants,id',
        ];
    }
}

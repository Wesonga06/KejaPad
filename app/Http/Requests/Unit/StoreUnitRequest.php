<?php

namespace App\Http\Requests\Unit;

use App\Models\Unit;
use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Unit::class, $this->route('property')]);
    }

    public function rules(): array
    {
        return [
            'unit_number' => 'required|string|max:50',
            'rent_amount' => 'required|numeric|min:0',
            'status' => 'required|in:vacant,occupied,reserved',
            'description' => 'nullable|string',
        ];
    }
}

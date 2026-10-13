<?php

namespace App\Http\Requests\Rent;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isLandlord();
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'amount_due' => 'required|numeric|min:1',
            'due_date' => 'required|date',
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'notes' => 'nullable|string',
        ];
    }
}

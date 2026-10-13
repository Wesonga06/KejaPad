<?php

namespace App\Http\Requests\Rent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordTenantPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tenant'));
    }

    public function rules(): array
    {
        return [
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'reference' => 'nullable|string|max:50',
            'method' => ['nullable', Rule::in(['mpesa', 'cash', 'bank_transfer', 'cheque', 'other'])],
            'policy' => ['nullable', Rule::in(['oldest', 'current', 'manual'])],
            'rent_record_id' => 'nullable|integer|exists:rent_records,id',
            'notes' => 'nullable|string|max:500',
        ];
    }
}

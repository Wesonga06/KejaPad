<?php

namespace App\Http\Requests\Settlement;

use App\Rules\PaymentMethodEnabled;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordSettlementRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('settlement'));
    }

    public function rules(): array
    {
        return [
            'method' => ['nullable', Rule::in(['mpesa', 'cash', 'bank_transfer', 'cheque', 'other']), new PaymentMethodEnabled],
            'reference' => 'nullable|string|max:100',
        ];
    }
}

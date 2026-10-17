<?php

namespace App\Http\Requests\Deposit;

use App\Rules\PaymentMethodEnabled;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordDepositReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('deposit'));
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'method' => ['nullable', Rule::in(['mpesa', 'cash', 'bank_transfer', 'cheque', 'other']), new PaymentMethodEnabled],
            'reference' => 'nullable|string|max:100',
            'verify' => 'nullable|boolean',
        ];
    }
}

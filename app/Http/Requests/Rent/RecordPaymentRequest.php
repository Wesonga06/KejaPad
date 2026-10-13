<?php

namespace App\Http\Requests\Rent;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordPayment', $this->route('rent'));
    }

    public function rules(): array
    {
        return [
            'amount_paid' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'transaction_code' => 'nullable|string|max:50',
        ];
    }
}

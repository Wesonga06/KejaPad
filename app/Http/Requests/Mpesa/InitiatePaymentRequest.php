<?php

namespace App\Http\Requests\Mpesa;

use Illuminate\Foundation\Http\FormRequest;

class InitiatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tenantProfile?->id === $this->route('rent')->tenant_id;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^(07|01|2547|2541)\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a valid Safaricom number e.g. 0712345678',
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-payments') ?? false;
    }

    public function rules(): array
    {
        return [
            'methods' => ['required', 'array', 'min:1'],
            'methods.*' => ['string', 'in:mpesa,cash,bank_transfer,cheque,other'],
            'require_verification' => 'required|boolean',
            'callback_enforce_ip' => 'required|boolean',
            'callback_allowed_ips' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'methods.required' => 'Select at least one payment method to accept.',
        ];
    }
}

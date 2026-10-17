<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SendTestSmsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-sms') ?? false;
    }

    public function rules(): array
    {
        return [
            'recipient' => ['required', 'string', 'max:20', 'regex:/^[0-9+()\- ]{7,20}$/'],
            'message' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient.regex' => 'Enter a valid phone number (digits, spaces, +, ( ) or -).',
        ];
    }
}

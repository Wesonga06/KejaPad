<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSmsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-sms') ?? false;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(['null', 'log', 'africastalking'])],
            'from' => 'nullable|string|max:20',
            'workflow_enabled' => 'required|boolean',
        ];
    }
}

<?php

namespace App\Http\Requests\Passport;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeUnitInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('acknowledge', $this->route('inspection'));
    }

    public function rules(): array
    {
        return [
            'acknowledge' => 'accepted',
            'acknowledgement_name' => 'nullable|string|max:191',
            'acknowledgement_note' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'acknowledge.accepted' => 'Please confirm you have reviewed this report before acknowledging.',
        ];
    }
}

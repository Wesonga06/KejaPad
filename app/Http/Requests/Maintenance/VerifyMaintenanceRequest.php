<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class VerifyMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('verify', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'verification_notes' => 'nullable|string|max:2000',
        ];
    }
}

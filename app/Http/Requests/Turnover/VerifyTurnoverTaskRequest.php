<?php

namespace App\Http\Requests\Turnover;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTurnoverTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('turnover'));
    }

    public function rules(): array
    {
        return [
            'verification_notes' => 'nullable|string',
        ];
    }
}

<?php

namespace App\Http\Requests\Turnover;

use Illuminate\Foundation\Http\FormRequest;

class MarkReadyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('confirmReady', $this->route('turnover'));
    }

    public function rules(): array
    {
        return [
            'readiness_notes' => 'nullable|string',
        ];
    }
}

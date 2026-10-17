<?php

namespace App\Http\Requests\Turnover;

use Illuminate\Foundation\Http\FormRequest;

class RecordHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('turnover'));
    }

    public function rules(): array
    {
        return [
            'handover_notes' => 'nullable|string',
            'keys_returned' => 'nullable|boolean',
        ];
    }
}

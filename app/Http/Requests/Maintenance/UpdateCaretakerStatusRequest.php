<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCaretakerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:in_progress,resolved',
            'resolution_notes' => 'nullable|string',
        ];
    }
}

<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class SubmitMaintenanceCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => 'required|string|max:4000',
            'evidence' => 'nullable|array|max:8',
            'evidence.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ];
    }
}

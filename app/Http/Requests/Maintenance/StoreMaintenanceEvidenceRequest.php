<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'evidence' => 'required|array|max:8',
            'evidence.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'note' => 'nullable|string|max:1000',
        ];
    }
}

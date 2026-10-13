<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,assigned,in_progress,resolved,closed',
            'resolution_notes' => 'nullable|string',
        ];
    }
}

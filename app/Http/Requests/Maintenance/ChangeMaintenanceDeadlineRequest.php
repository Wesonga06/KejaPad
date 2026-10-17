<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class ChangeMaintenanceDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'due_date' => 'nullable|date',
        ];
    }
}

<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assign', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', 'caretaker'),
            ],
            'due_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'assigned_to.exists' => 'The selected user is not a caretaker.',
        ];
    }
}

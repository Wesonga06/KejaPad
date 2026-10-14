<?php

namespace App\Http\Requests\MoveOut;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('assignInspection', $this->route('moveOut'));
    }

    public function rules(): array
    {
        return [
            'caretaker_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'caretaker'),
            ],
            'scheduled_at' => 'nullable|date',
        ];
    }
}

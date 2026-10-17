<?php

namespace App\Http\Requests\Turnover;

use Illuminate\Foundation\Http\FormRequest;

class AssignTurnoverTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('turnover'));
    }

    public function rules(): array
    {
        return [
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }
}

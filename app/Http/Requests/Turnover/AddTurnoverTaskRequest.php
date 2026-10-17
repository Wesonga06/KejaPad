<?php

namespace App\Http\Requests\Turnover;

use Illuminate\Foundation\Http\FormRequest;

class AddTurnoverTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('turnover'));
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|in:plumbing,electrical,security,cleaning,general',
            'priority' => 'nullable|in:low,medium,high',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ];
    }
}

<?php

namespace App\Http\Requests\MoveOut;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClearMoveOutRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('clear', $this->route('moveOut'));
    }

    public function rules(): array
    {
        return [
            'clearance_decision' => ['required', Rule::in(['cleared', 'cleared_with_notes'])],
            'actual_move_out_date' => 'required|date',
            'notes' => 'nullable|string|max:2000|required_if:clearance_decision,cleared_with_notes',
        ];
    }
}

<?php

namespace App\Http\Requests\MoveOut;

use App\Models\MoveOutRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreMoveOutRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MoveOutRequest::class);
    }

    public function rules(): array
    {
        return [
            'proposed_move_out_date' => 'required|date|after_or_equal:today',
            'reason' => 'nullable|string|max:1000',
        ];
    }
}

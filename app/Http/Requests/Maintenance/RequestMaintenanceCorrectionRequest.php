<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class RequestMaintenanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenance'))
            || $this->user()->can('view', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:2000',
        ];
    }
}

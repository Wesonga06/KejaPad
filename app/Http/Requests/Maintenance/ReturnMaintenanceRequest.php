<?php

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class ReturnMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('returnTask', $this->route('maintenance'));
    }

    public function rules(): array
    {
        return [
            'return_reason' => 'required|string|max:2000',
        ];
    }
}

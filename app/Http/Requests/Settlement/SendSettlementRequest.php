<?php

namespace App\Http\Requests\Settlement;

use Illuminate\Foundation\Http\FormRequest;

class SendSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('settlement'));
    }

    public function rules(): array
    {
        return [
            'landlord_findings' => 'nullable|string|max:2000',
        ];
    }
}

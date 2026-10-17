<?php

namespace App\Http\Requests\CaretakerInvitation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCaretakerInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isLandlord();
    }

    public function rules(): array
    {
        return [
            'property_id' => [
                'required',
                Rule::exists('properties', 'id')->where('user_id', $this->user()->id),
            ],
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'property_id.exists' => 'Select one of your own properties.',
        ];
    }
}

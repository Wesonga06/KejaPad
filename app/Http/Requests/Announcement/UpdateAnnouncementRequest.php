<?php

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('announcement'));
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'required|in:general,water,electricity,security,maintenance,other',
            'audience' => 'required|in:all,tenants,caretakers',
            'property_id' => [
                'nullable',
                Rule::exists('properties', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }
}

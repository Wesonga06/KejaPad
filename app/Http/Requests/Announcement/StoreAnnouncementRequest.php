<?php

namespace App\Http\Requests\Announcement;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Announcement::class);
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

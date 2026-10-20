<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'total_units' => 'required|integer|min:1',
            'status' => 'required|in:active,inactive',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'caretakers' => 'nullable|array',
            'caretakers.*' => [
                'integer',
                Rule::exists('users', 'id')->where('role', 'caretaker'),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->has('total_units') || ! $this->has('total_units')) {
                return;
            }

            $property = $this->route('property');
            $registeredUnits = $property->units()->count();
            $capacity = (int) $this->input('total_units');

            if ($capacity < $registeredUnits) {
                $validator->errors()->add(
                    'total_units',
                    "The listed capacity cannot be reduced to {$capacity} because {$registeredUnits} units are already registered. Set capacity to at least {$registeredUnits} units."
                );
            }
        }];
    }
}

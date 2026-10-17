<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\- ]{7,20}$/'],
            'role' => ['required', 'in:super_admin,landlord,caretaker,tenant'],
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|min:8|confirmed',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->route('user');

            if ($user === null || $user->id !== $this->user()->id) {
                return;
            }

            // An administrator may neither demote themselves nor deactivate
            // themselves - the platform must never lock itself out.
            if ($this->input('role') !== $user->role) {
                $validator->errors()->add('role', 'You cannot change your own role.');
            }

            if ($this->has('is_active') && ! $this->boolean('is_active')) {
                $validator->errors()->add('is_active', 'You cannot deactivate your own account.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone must contain only digits, spaces, +, ( ) or -.',
        ];
    }
}

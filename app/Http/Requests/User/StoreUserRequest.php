<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\- ]{7,20}$/'],
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:super_admin,landlord,caretaker,tenant',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone must contain only digits, spaces, +, ( ) or -.',
        ];
    }
}

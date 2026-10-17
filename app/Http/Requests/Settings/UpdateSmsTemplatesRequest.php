<?php

namespace App\Http\Requests\Settings;

use App\Models\SmsTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSmsTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-sms') ?? false;
    }

    public function rules(): array
    {
        return [
            'templates' => ['required', 'array'],
            'templates.*' => ['required', 'string', 'max:500', 'regex:/[A-Za-z0-9]/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $known = SmsTemplate::pluck('key')->all();

            foreach (array_keys($this->input('templates', [])) as $key) {
                if (! in_array($key, $known, true)) {
                    $validator->errors()->add("templates.{$key}", 'Unknown SMS template.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'templates.*.regex' => 'Template text cannot be blank.',
        ];
    }
}

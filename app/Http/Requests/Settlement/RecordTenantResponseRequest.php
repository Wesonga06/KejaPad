<?php

namespace App\Http\Requests\Settlement;

use Illuminate\Foundation\Http\FormRequest;

class RecordTenantResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('respond', $this->route('settlement'));
    }

    public function rules(): array
    {
        return [
            'tenant_comment' => 'nullable|string|max:2000',
            'acknowledge' => 'nullable|boolean',
            'dispute' => 'nullable|array',
            'dispute.*' => 'integer|exists:deposit_transactions,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (blank($this->input('tenant_comment'))
                && ! $this->boolean('acknowledge')
                && empty($this->input('dispute', []))) {
                $validator->errors()->add('tenant_comment', 'Please add a comment, dispute a deduction, or acknowledge the statement.');
            }
        });
    }
}

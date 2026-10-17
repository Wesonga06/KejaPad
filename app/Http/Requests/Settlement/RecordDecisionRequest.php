<?php

namespace App\Http\Requests\Settlement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('settlement'));
    }

    public function rules(): array
    {
        return [
            'decisions' => 'nullable|array',
            'decisions.*' => ['nullable', Rule::in(['approve', 'reject'])],
            'approve' => 'nullable|array',
            'approve.*' => 'integer|exists:deposit_transactions,id',
            'reject' => 'nullable|array',
            'reject.*' => 'integer|exists:deposit_transactions,id',
            'amounts' => 'nullable|array',
            'amounts.*' => 'nullable|numeric|min:0',
            'approved_refund' => 'nullable|numeric|min:0',
            'confirm_authorisation' => 'nullable|boolean',
            'landlord_findings' => 'nullable|string|max:2000',
            'decision_reason' => 'nullable|string|max:1000',
        ];
    }
}

<?php

namespace App\Http\Requests\MoveOut;

use App\Models\MoveOutRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewMoveOutRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('moveOut'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                MoveOutRequest::STATUS_UNDER_REVIEW,
                MoveOutRequest::STATUS_AWAITING_TENANT_INFO,
                MoveOutRequest::STATUS_APPROVED,
                MoveOutRequest::STATUS_REJECTED,
            ])],
            'decision_reason' => 'nullable|string|max:1000|required_if:status,'.MoveOutRequest::STATUS_REJECTED,
            'clarification_message' => 'nullable|string|max:1000|required_if:status,'.MoveOutRequest::STATUS_AWAITING_TENANT_INFO,
        ];
    }
}

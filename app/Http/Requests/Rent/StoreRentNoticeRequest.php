<?php

namespace App\Http\Requests\Rent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('tenant'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                'payment_reminder',
                'follow_up_reminder',
                'escalation',
                'formal_notice',
                'demand_letter',
                'other',
            ])],
            'issued_at' => 'required|date',
            'recipient' => 'nullable|string|max:255',
            'delivery_status' => ['nullable', Rule::in(['draft', 'sent', 'delivered', 'failed', 'acknowledged'])],
            'deadline' => 'nullable|date|after_or_equal:issued_at',
            'notes' => 'nullable|string|max:2000',
            'rent_record_id' => 'nullable|integer|exists:rent_records,id',
        ];
    }
}

<?php

namespace App\Http\Requests\Rent;

use App\Models\RentRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordPayment', $this->route('rent'));
    }

    public function rules(): array
    {
        return [
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'transaction_code' => 'nullable|string|max:50',
            'method' => ['nullable', Rule::in(['mpesa', 'cash', 'bank_transfer', 'cheque', 'other'])],
            'policy' => ['nullable', Rule::in(['oldest', 'current', 'manual'])],
            'rent_record_id' => 'nullable|integer|exists:rent_records,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $rent = $this->route('rent');

            // Guard against recording more than the true outstanding balance unless a
            // credit/overpayment is intentional. We only block when overpaying without
            // an explicit split, to keep balancing predictable.
            if ($rent instanceof RentRecord && $this->filled('rent_record_id')) {
                $target = RentRecord::find($this->input('rent_record_id'));

                if ($target && $target->tenant_id !== $rent->tenant_id) {
                    $validator->errors()->add('rent_record_id', 'The selected rent period does not belong to this tenant.');
                }
            }
        });
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// Rejects a payment method that the platform administrator has disabled.
class PaymentMethodEnabled implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $enabled = (array) config('payments.methods_enabled', [
            'mpesa', 'cash', 'bank_transfer', 'cheque', 'other',
        ]);

        if (! in_array($value, $enabled, true)) {
            $fail('The selected :attribute is currently disabled by the platform.');
        }
    }
}

<?php

namespace App\Http\Requests\LandlordApproval;

use Illuminate\Foundation\Http\FormRequest;

class SuspendLandlordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin() && $this->route('landlord')->isLandlord();
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|min:3|max:1000',
        ];
    }
}

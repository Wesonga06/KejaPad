<?php

namespace App\Http\Requests\Passport;

use App\Models\UnitDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', UnitDocument::class)
            && $this->user()->can('managePassport', $this->route('unit'));
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:191',
            'type' => ['required', Rule::in(array_keys(UnitDocument::types()))],
            'visibility' => ['required', Rule::in(array_keys(UnitDocument::visibilities()))],
            'note' => 'nullable|string|max:1000',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx|max:10240',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', UnitDocument::TYPE_OTHER),
            'visibility' => $this->input('visibility', UnitDocument::VISIBILITY_LANDLORD),
        ]);
    }
}

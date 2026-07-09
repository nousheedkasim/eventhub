<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['sometimes', 'string', 'max:255'],
            'contact_person' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:vendors,email,' . $this->route('vendor')->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],

            'address' => ['sometimes', 'nullable', 'string'],
            'website' => ['sometimes', 'nullable', 'string', 'max:255'],

            'kyc_status' => ['sometimes', 'nullable', 'in:pending,verified,rejected'],
            'kyc_notes' => ['sometimes', 'nullable', 'string'],

            'bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'account_holder_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'iban' => ['sometimes', 'nullable', 'string', 'max:255'],
            'swift_code' => ['sometimes', 'nullable', 'string', 'max:255'],

            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}


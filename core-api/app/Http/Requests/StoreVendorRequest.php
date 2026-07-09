<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:vendors,email'],
            'phone' => ['nullable', 'string', 'max:50'],

            'address' => ['nullable', 'string'],
            'website' => ['nullable', 'string', 'max:255'],

            'kyc_status' => ['nullable', 'in:pending,verified,rejected'],
            'kyc_notes' => ['nullable', 'string'],

            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
            'swift_code' => ['nullable', 'string', 'max:255'],

            'is_active' => ['nullable', 'boolean'],
        ];
    }
}


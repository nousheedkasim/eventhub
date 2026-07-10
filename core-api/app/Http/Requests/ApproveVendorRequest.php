<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'min:1', 'exists:vendors,id'],
            'kyc_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
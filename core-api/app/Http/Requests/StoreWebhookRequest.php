<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'min:1', 'exists:vendors,id'],
            'url' => ['required', 'string', 'max:500'],
            'secret' => ['required', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}


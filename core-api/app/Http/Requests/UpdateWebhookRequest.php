<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['sometimes', 'integer', 'min:1', 'exists:vendors,id'],
            'url' => ['sometimes', 'string', 'max:500'],
            'secret' => ['sometimes', 'string', 'max:255'],
            'active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}


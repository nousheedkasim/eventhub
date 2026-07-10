<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'gateway' => ['required', 'string', 'in:stripe,paypal'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }
}


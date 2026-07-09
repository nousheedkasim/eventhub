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
            'order_id' => ['required', 'integer', 'min:1', 'exists:orders,id'],
            'gateway' => ['required', 'string', 'max:50'],
            'status' => ['required', 'string', 'in:pending,authorized,paid,failed,refunded'],
            'idempotency_key' => ['required', 'string', 'max:255', 'unique:payments,idempotency_key'],
            'gateway_reference' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}


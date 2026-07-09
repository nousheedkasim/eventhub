<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'integer', 'min:1', 'exists:orders,id'],
            'gateway' => ['sometimes', 'string', 'max:50'],
            'status' => ['sometimes', 'string', 'in:pending,authorized,paid,failed,refunded'],
            'idempotency_key' => ['sometimes', 'string', 'max:255', 'unique:payments,idempotency_key,' . ($this->route('payment')?->id ?? 'NULL')],
            'gateway_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}


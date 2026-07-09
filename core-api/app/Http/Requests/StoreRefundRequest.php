<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'integer', 'min:1', 'exists:payments,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'policy_applied' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:pending,approved,completed,failed'],
            'reason' => ['nullable', 'string'],
            'refunded_at' => ['nullable', 'date'],
        ];
    }
}


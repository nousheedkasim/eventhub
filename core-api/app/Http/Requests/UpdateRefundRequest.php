<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_id' => ['sometimes', 'integer', 'min:1', 'exists:payments,id'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'policy_applied' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:pending,approved,completed,failed'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'refunded_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}


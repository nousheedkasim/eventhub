<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['sometimes', 'integer', 'min:1', 'exists:vendors,id'],
            'payout_batch_id' => ['sometimes', 'nullable', 'integer', 'exists:payout_batches,id'],
            'gross_amount' => ['sometimes', 'numeric', 'min:0'],
            'commission' => ['sometimes', 'numeric', 'min:0'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:pending,processing,paid,failed'],
            'transfer_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}


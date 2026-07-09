<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'min:1', 'exists:vendors,id'],
            'payout_batch_id' => ['nullable', 'integer', 'exists:payout_batches,id'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'commission' => ['required', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:pending,processing,paid,failed'],
            'transfer_reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}


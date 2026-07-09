<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayoutBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_reference' => ['required', 'string', 'max:100', 'unique:payout_batches,batch_reference'],
            'status' => ['required', 'string', 'in:pending,running,completed,failed'],
            'total_payouts' => ['required', 'integer', 'min:0'],
            'processed_count' => ['required', 'integer', 'min:0'],
            'resume_token' => ['nullable', 'string', 'max:255'],
            'started_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}


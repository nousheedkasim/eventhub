<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayoutBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_reference' => ['sometimes', 'string', 'max:100', 'unique:payout_batches,batch_reference,' . $this->route('payout-batches')?->id],
            'status' => ['sometimes', 'string', 'in:pending,running,completed,failed'],
            'total_payouts' => ['sometimes', 'integer', 'min:0'],
            'processed_count' => ['sometimes', 'integer', 'min:0'],
            'resume_token' => ['sometimes', 'nullable', 'string', 'max:255'],
            'started_at' => ['sometimes', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}


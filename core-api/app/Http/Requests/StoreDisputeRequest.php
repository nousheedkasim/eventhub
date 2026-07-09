<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'min:1', 'exists:orders,id'],
            'status' => ['required', 'string', 'in:open,investigating,resolved,rejected'],
            'reason' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
            'resolved_at' => ['nullable', 'date'],
        ];
    }
}


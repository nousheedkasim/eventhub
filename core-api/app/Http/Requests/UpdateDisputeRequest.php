<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'integer', 'min:1', 'exists:orders,id'],
            'status' => ['sometimes', 'string', 'in:open,investigating,resolved,rejected'],
            'reason' => ['sometimes', 'nullable', 'string'],
            'resolution' => ['sometimes', 'nullable', 'string'],
            'resolved_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}


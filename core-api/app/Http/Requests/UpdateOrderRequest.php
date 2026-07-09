<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendee_id' => ['sometimes', 'integer', 'min:1'],
            'status' => [
                'sometimes',
                'string',
                'in:pending,held,paid,cancelled,expired,refunded',
            ],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'hold_expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}


<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendee_id' => ['required', 'integer', 'min:1'],
            'status' => [
                'required',
                'string',
                'in:pending,held,paid,cancelled,expired,refunded',
            ],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'hold_expires_at' => ['nullable', 'date'],
        ];
    }
}


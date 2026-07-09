<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['sometimes', 'integer', 'exists:events,id'],
            'type' => ['sometimes', 'string', 'max:100'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'inventory' => ['sometimes', 'integer', 'min:0'],
            'sold_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'available_from' => ['sometimes', 'nullable', 'date'],
            'available_until' => ['sometimes', 'nullable', 'date', 'after_or_equal:available_from'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}


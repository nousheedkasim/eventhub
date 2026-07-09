<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'type' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'inventory' => ['required', 'integer', 'min:0'],
            'sold_count' => ['nullable', 'integer', 'min:0'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}


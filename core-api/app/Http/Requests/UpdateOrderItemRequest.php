<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['sometimes', 'integer', 'exists:orders,id'],
            'ticket_type_id' => ['sometimes', 'integer', 'exists:ticket_types,id'],
            'qty' => ['sometimes', 'integer', 'min:1'],
            'price_at_purchase' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}


<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'ticket_type_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'price_at_purchase' => ['required', 'numeric', 'min:0'],
        ];
    }
}


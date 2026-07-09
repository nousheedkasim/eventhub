<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_user_id' => ['required', 'integer', 'min:1', 'exists:users,id'],
            'type' => ['required', 'string', 'max:100'],
            'channel' => ['required', 'string', 'max:30'],
            'status' => ['required', 'string', 'in:pending,sent,failed'],
            'retry_count' => ['required', 'integer', 'min:0'],
            'payload' => ['required', 'string'],
            'sent_at' => ['nullable', 'date'],
        ];
    }
}


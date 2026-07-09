<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_user_id' => ['sometimes', 'integer', 'min:1', 'exists:users,id'],
            'type' => ['sometimes', 'string', 'max:100'],
            'channel' => ['sometimes', 'string', 'max:30'],
            'status' => ['sometimes', 'string', 'in:pending,sent,failed'],
            'retry_count' => ['sometimes', 'integer', 'min:0'],
            'payload' => ['sometimes', 'string'],
            'sent_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}


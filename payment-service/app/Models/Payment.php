<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'amount',
        'currency',
        'gateway',
        'status',
        'idempotency_key',
        'payment_reference',
        'event_date',
        'refunded_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'event_date' => 'datetime',
    ];
}

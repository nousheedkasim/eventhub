<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'gateway',
        'status',
        'idempotency_key',
        'gateway_reference',
        'amount',
        'currency',
        'paid_at',
    ];

    protected $casts = [
        'status' => 'string',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}


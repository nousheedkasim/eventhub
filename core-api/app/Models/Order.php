<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'attendee_id',
        'status',
        'total_amount',
        'hold_expires_at',
    ];

    protected $casts = [
        'status' => 'string',
        'total_amount' => 'decimal:2',
        'hold_expires_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events()
    {
        return $this->hasMany(OrderEvent::class);
    }
}


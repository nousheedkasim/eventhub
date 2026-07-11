<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'payment_id',
        'amount',
        'policy_applied',
        'status',
        'reason',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'status' => 'string',
        'refunded_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'refund_id',
        'from_status',
        'to_status',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }
}

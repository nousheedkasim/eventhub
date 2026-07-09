<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'recipient_user_id',
        'type',
        'channel',
        'status',
        'retry_count',
        'payload',
        'sent_at',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'sent_at' => 'datetime',
        'status' => 'string',
    ];

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }
}


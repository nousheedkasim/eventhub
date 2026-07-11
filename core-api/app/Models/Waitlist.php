<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waitlist extends Model
{
    protected $fillable = [
        'ticket_type_id',
        'user_id',
        'priority_index',
        'notified',
    ];

    protected $casts = [
        'priority_index' => 'integer',
        'notified' => 'boolean',
    ];

    public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

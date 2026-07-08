<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReservation extends Model
{
    // Allows these fields to be set via create()
    protected $fillable = [
        'user_id',
        'ticket_type_id',
        'quantity',
        'session_token', 
        'status',
        'expires_at'
    ];

    // Cast the column to a date object automatically
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function ticketType()
    {
        return $this->belongsTo(\App\Models\TicketType::class);
    }
}


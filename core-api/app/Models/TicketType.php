<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketType extends Model
{
    // Tells Laravel exactly which table to use
    protected $table = 'ticket_types';

    // Allows you to create rows using create() or fill()
    protected $fillable = [
        'event_id', 
        'name', 
        'price_cents', 
        'total_capacity', 
        'remaining_inventory'
    ];
}
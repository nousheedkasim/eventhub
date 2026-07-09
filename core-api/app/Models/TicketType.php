<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'event_id',
        'type',
        'price',
        'inventory',
        'sold_count',
        'available_from',
        'available_until',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'inventory' => 'integer',
        'sold_count' => 'integer',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}


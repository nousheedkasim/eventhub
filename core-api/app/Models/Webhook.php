<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'vendor_id',
        'url',
        'secret',
        'events',
        'active',
        'is_active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}


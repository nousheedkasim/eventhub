<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'title',
        'description',
        'location',
        'event_date',
    ];

    protected $casts = [
        'event_date' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reminders()
    {
        return $this->hasMany(Notification::class)->where('type', 'event_reminder');
    }
}

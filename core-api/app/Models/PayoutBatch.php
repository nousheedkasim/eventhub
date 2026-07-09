<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayoutBatch extends Model
{
    protected $fillable = [
        'batch_reference',
        'status',
        'total_payouts',
        'processed_count',
        'resume_token',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => 'string',
        'total_payouts' => 'integer',
        'processed_count' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'vendor_id',
        'payout_batch_id',
        'gross_amount',
        'commission',
        'amount',
        'status',
        'transfer_reference',
        'paid_at',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'status' => 'string',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function payoutBatch()
    {
        return $this->belongsTo(PayoutBatch::class);
    }
}


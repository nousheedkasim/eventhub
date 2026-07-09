<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;


    protected $fillable = [

        'company_name',
        'contact_person',
        'email',
        'phone',

        'address',
        'website',

        'kyc_status',
        'kyc_notes',

        'bank_name',
        'account_holder_name',
        'account_number',
        'iban',
        'swift_code',

        'is_active'
    ];


    protected $casts = [

        'is_active' => 'boolean',

    ];


    /*
     |--------------------------------------------------------------------------
     | Relationships
     |--------------------------------------------------------------------------
     */


    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
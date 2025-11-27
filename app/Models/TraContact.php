<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TraContact extends Model
{
    protected $table = 'tra_contacts';

    protected $fillable = [
        'first_name',
        'last_name',
        'lead_time_stamp',
        'email',
        'email_domain',
        'phone',
        'state',
        'zip_code',
        'page',
        'optin_domain',
        'universal_leadid',
        'cake_id',
        'ckm_campaign_id',
        'ckm_key',
        'tax_debt',
        'aff_id',
        'sub_id',
        'ip_address',
        'offer_id',
        'response',
    ];

    protected $casts = [
        'lead_time_stamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}


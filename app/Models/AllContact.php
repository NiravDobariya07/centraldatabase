<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllContact extends Model
{
    protected $table = 'all_contacts';

    protected $fillable = [
        'first_name',
        'last_name',
        'lead_time_stamp',
        'email',
        'email_domain',
        'phone',
        'optin_domain',
        'domain_abt',
        'aff_id',
        'sub_id',
        'cake_leadid',
        'result',
        'resultid',
        'response',
        'journya',
        'trusted_form',
        'ip_address',
        'esp',
        'offer_id',
        'is_email_duplicate',
        'list_id',
        'eoapi_success',
        'is_ongage',
        'ongage_response',
        'ongage_at',
    ];

    protected $casts = [
        'lead_time_stamp' => 'datetime',
        'is_email_duplicate' => 'boolean',
        'eoapi_success' => 'boolean',
        'is_ongage' => 'boolean',
        'ongage_at' => 'datetime',
        'resultid' => 'integer',
        'offer_id' => 'integer',
        'list_id' => 'integer',
    ];
}

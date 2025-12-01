<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlmApiLead extends Model
{
    protected $table = 'flm_api_leads';

    protected $fillable = [
        'first_name',
        'email_address',
        'lead_timestamp',
        'fetch_paid_response',
        'payout_paid',
        'eoapi_success',
        'is_email_duplicate',
        'result',
        'resultid',
        'response',
        'is_ongage',
        'ongage_response',
        'ongage_at',
        'lead_id',
    ];

    protected $casts = [
        'eoapi_success' => 'boolean',
        'is_email_duplicate' => 'boolean',
        'is_ongage' => 'boolean',
        'resultid' => 'integer',
        'ongage_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}


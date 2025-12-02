<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtLeadContact extends Model
{
    protected $table = 'ext_lead_contact';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'alt_phone',
        'address',
        'city',
        'state',
        'postal',
        'country',
        'ip',
        'date_subscribed',
        'gender',
        'offer_url',
        'dob',
        'list_id',
        'import_date',
        'phone_type',
        'tax_debt_amount',
        'type_of_debt',
        'homeowner',
        'jornaya_id',
        'trusted_form_id',
        'opt_in',
        'subid1',
        'subid2',
        'subid3',
        'subid4',
        'subid5',
        'aff_id_1',
        'aff_id_2',
        'lead_id',
        'page_url',
        'ef_id',
        'ck_id',
        'source',
        'affid',
        'subid',
        'result',
        'resultid',
        'response',
        'is_email_duplicate',
        'eoapi_success',
        'is_ongage',
        'ongage_response',
        'ongage_at',
        'created_date',
    ];

    protected $casts = [
        'is_email_duplicate' => 'boolean',
        'eoapi_success' => 'boolean',
        'is_ongage' => 'boolean',
        'resultid' => 'integer',
        'ongage_at' => 'datetime',
        'created_date' => 'datetime',
    ];

    public $timestamps = false; // Using created_date instead of created_at/updated_at
}

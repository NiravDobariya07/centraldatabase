<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $table = 'leads';

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
        'tax_debt_amount',
        'cc_debt_amount',
        'type_of_debt',
        'home_owner',
        'import_date',
        'jornaya_id',
        'phone_type',
        'trusted_form_id',
        'opt_in',
        'sub_id_1',
        'sub_id_2',
        'sub_id_3',
        'sub_id_4',
        'sub_id_5',
        'aff_id_1',
        'aff_id_2',
        'lead_id',
        'ef_id',
        'ck_id',
        'page_url',
        'extra_fields',
        'search_vector',
        'source_site_id',
        'campaign_list_id'
    ];

    protected $casts = [
        'date_subscribed' => 'datetime',
        'import_date' => 'datetime',
        'dob' => 'date'
    ];

    protected $appends = ['source_site_data', 'campaign_list_data'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($lead) {
            $fields = [
                'first_name', 'last_name', 'email', 'phone', 'alt_phone', 'address', 'city',
                'state', 'postal', 'country', 'lead_id', 'jornaya_id', 'trusted_form_id',
                'tax_debt_amount', 'cc_debt_amount', 'type_of_debt', 'home_owner', 'offer_url',
                'page_url', 'sub_id_1', 'sub_id_2', 'sub_id_3', 'sub_id_4',
                'sub_id_5', 'aff_id_1', 'aff_id_2', 'ef_id', 'ck_id'
            ];

            // Filter and concatenate fields into a single search vector string
            $lead->search_vector = implode(' ', array_filter(
                array_map(fn($field) => trim($lead->$field ?? ''), $fields),
                fn($value) => !empty($value)
            ));
        });
    }

    // Mutator: Store 'extra_fields' data as JSON string
    public function setExtraFieldsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['extra_fields'] = json_encode($value);
        } else {
            $this->attributes['extra_fields'] = null;
        }
    }

    // Accessor: Retrieve 'extra_fields' as an array
    public function getExtraFieldsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $decoded;
    }

    public function sourceSite() {
        return $this->belongsTo(SourceSite::class, 'source_site_id', 'id');
    }

    public function campaignList() {
        return $this->belongsTo(CampaignListId::class, 'campaign_list_id', 'id');
    }

    public function getSourceSiteDataAttribute() {
        return $this->sourceSite ? $this->sourceSite : null;
    }

    public function getCampaignListDataAttribute() {
        return $this->campaignList ? $this->campaignList : null;
    }
}
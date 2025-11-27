<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumerInsiteContact extends Model
{
    protected $table = 'consumer_insite_contacts';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'age',
        'credit_score',
        'location_name',
        'is_email_duplicate',
        'eoapi_success',
        'result',
        'resultid',
        'response',
        'is_ongage',
        'ongage_response',
        'deleted_at',
    ];

    protected $casts = [
        'is_email_duplicate' => 'boolean',
        'eoapi_success' => 'boolean',
        'is_ongage' => 'boolean',
        'resultid' => 'integer',
        'deleted_at' => 'integer',
    ];

    /**
     * Get the categories for this contact
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_consumer_insite_contact', 'consumer_insite_contact_id', 'category_id');
    }

    /**
     * Get the first category (for display purposes)
     */
    public function category()
    {
        return $this->categories()->first();
    }
}

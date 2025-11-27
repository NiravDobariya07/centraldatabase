<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'category_name',
    ];

    // Only use created_at, not updated_at
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    /**
     * Get the consumer insite contacts for this category
     */
    public function consumerInsiteContacts()
    {
        return $this->belongsToMany(ConsumerInsiteContact::class, 'category_consumer_insite_contact', 'category_id', 'consumer_insite_contact_id');
    }
}

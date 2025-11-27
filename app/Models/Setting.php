<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'user_id',
        'lead_fields',
        'consumer_insite_contact_fields',
        'tra_contact_fields'
    ];
}

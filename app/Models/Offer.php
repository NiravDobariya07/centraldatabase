<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    protected $table = 'offers';

    protected $fillable = [
        'offer_name',
        'domain_abt',
        'auth_token',
    ];

    protected $dates = ['deleted_at'];
}

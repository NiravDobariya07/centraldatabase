<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlacklistListing extends Model
{
    use SoftDeletes;

    protected $table = 'blacklist_listings';

    protected $fillable = [
        'email',
        'response',
        'source_type',
        'source',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}

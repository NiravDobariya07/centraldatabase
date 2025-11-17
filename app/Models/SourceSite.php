<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceSite extends Model
{
    protected $table = 'source_sites';

    protected $fillable = [
        'domain'
    ];

    public function leads() {
        return $this->hasMany(Lead::class, 'source_site_id', 'id');
    }
}

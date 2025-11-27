<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SourceSite extends Model
{
    protected $table = 'source_sites';

    protected $fillable = [
        'domain'
    ];

    // Note: all_contacts table doesn't have source_site_id column
    // This relationship may not work if the foreign key doesn't exist
    public function allContacts() {
        return $this->hasMany(AllContact::class, 'optin_domain', 'domain');
    }

    // Keep leads() for backward compatibility if needed
    public function leads() {
        return $this->allContacts();
    }
}

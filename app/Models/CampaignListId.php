<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampaignListId extends Model
{
    use HasFactory;

    protected $table = 'campaign_list_ids';
    protected $fillable = ['list_id'];

    public function allContacts()
    {
        // Note: all_contacts table has list_id directly, not campaign_list_id
        // This relationship may not work if the foreign key doesn't exist
        return $this->hasMany(AllContact::class, 'list_id', 'list_id');
    }

    // Keep leads() for backward compatibility if needed
    public function leads()
    {
        return $this->allContacts();
    }
}

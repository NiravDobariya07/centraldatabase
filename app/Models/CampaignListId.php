<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CampaignListId extends Model
{
    use HasFactory;

    protected $table = 'campaign_list_ids';
    protected $fillable = ['list_id'];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'campaign_list_id', 'id');
    }
}

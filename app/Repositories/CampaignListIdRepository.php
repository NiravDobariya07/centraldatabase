<?php
namespace App\Repositories;

class CampaignListIdRepository extends Repository
{
    protected $model;
    protected $model_name = "App\Models\CampaignListId";

    public function __construct()
    {
        parent::__construct();
    }
}
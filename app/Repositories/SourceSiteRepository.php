<?php
namespace App\Repositories;

class SourceSiteRepository extends Repository
{
    protected $model;
    protected $model_name = "App\Models\SourceSite";

    public function __construct()
    {
        parent::__construct();
    }
}
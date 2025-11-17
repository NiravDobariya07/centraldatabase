<?php
namespace App\Repositories;

class LeadRepository extends Repository
{
    protected $model;
    protected $model_name = "App\Models\Lead";

    public function __construct()
    {
        parent::__construct();
    }
}
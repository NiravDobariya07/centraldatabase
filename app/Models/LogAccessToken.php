<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LogAccessToken extends Model
{
    protected $fillable = ['request_ip', 'token', 'expires_at'];

    public $timestamps = true;

    // Check if the token is expired
    public function isExpired()
    {
        return $this->expires_at < Carbon::now();
    }
}

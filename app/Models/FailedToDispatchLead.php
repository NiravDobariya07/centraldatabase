<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedToDispatchLead extends Model
{
    protected $table = 'failed_to_dispatch_leads';

    protected $fillable = [
        'payload',
        'error_message',
        'exception_code',
        'exception_file',
        'exception_line',
        'stack_trace',
        'client_ip',
        'user_agent',
        'request_url',
    ];

    // Cast the payload field to an array automatically
    protected $casts = [
        'payload' => 'array',
    ];
}
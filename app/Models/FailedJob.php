<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FailedJob extends Model
{
    use HasFactory;

    protected $table = 'failed_jobs'; // Explicitly define the table name

    protected $fillable = [
        'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at'
    ];

    public $timestamps = false; // The `failed_jobs` table does not have updated_at and created_at columns
}

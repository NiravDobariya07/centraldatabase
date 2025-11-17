<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogCategory extends Model {
    use HasFactory;

    protected $fillable = ['name'];

    public function logLevels() {
        return $this->belongsToMany(LogLevel::class, 'log_category_log_level', 'log_category_id', 'log_level_id');
    }
}
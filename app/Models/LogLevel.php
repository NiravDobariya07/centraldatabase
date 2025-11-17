<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogLevel extends Model {
    use HasFactory;

    protected $fillable = ['name', 'description', 'enabled'];

    public function logCategories() {
        return $this->belongsToMany(LogCategory::class, 'log_category_log_level', 'log_level_id', 'log_category_id');
    }
}
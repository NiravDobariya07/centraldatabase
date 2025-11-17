<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Export extends Model
{
    use HasFactory;

    protected $table = 'exports';
    protected $fillable = [
        'user_id',
        'title', 
        'description', 
        'file_prefix', 
        'export_formats', 
        'filters',
        'additional_data',
        'columns',
        'frequency', 
        'day_of_week', 
        'day_of_month', 
        'time', 
        'next_run_at', 
        'last_run_at',
        'runing_status',
        'status',
    ];
    
    protected $casts = [
        'export_formats'  => 'array',
        'filters'         => 'array',
        'columns'         => 'array',
        'additional_data' => 'array',
        'next_run_at'     => 'datetime',
        'last_run_at'     => 'datetime',
    ];

    public function calculateNextRun()
    {
        $now = now();

        switch ($this->frequency) {
            case 'one_time':
                return null;

            case 'daily':
                $scheduledTime = $now->copy()->setTimeFromTimeString($this->time);
                return $scheduledTime->greaterThan($now) ? $scheduledTime : $now->addDay()->setTimeFromTimeString($this->time);

            case 'weekly':
                $scheduledTime = $now->copy()->next($this->day_of_week)->setTimeFromTimeString($this->time);
                $todayTime = $now->copy()->setTimeFromTimeString($this->time);

                return $todayTime->greaterThan($now) && $now->dayOfWeek == $this->day_of_week
                    ? $todayTime
                    : $scheduledTime;

            case 'monthly':
                $scheduledTime = $now->copy()->day((int) $this->day_of_month)->setTimeFromTimeString($this->time);
                return $scheduledTime->greaterThan($now) ? $scheduledTime : $now->addMonth()->day((int) $this->day_of_month)->setTimeFromTimeString($this->time);

            default:
                return null;
        }
    }

    // Define relationship with User
    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }

    public function exportFiles() {
        return $this->hasMany(ExportFile::class, 'export_id', 'id');
    }
}

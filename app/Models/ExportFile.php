<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Constants\AppConstants;

class ExportFile extends Model
{
    use HasFactory;

    protected $table = 'export_files';
    protected $fillable = [
        'export_id',
        'user_id',
        'file_name',
        'file_path',
        'file_format',
        'file_size',
        'generated_at',
        'expires_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $appends = ['file_size_kb', 'file_size_mb', 'file_size_gb'];

    // Boot method to listen to the delete event
    protected static function booted()
    {
        static::deleted(function ($exportFile) {
            // Check if file exists before attempting deletion
            if (Storage::disk('local')->exists($exportFile->file_path)) {

                // Deleting the file from storage
                Storage::disk('local')->delete($exportFile->file_path);

                // Log after file deletion
                customLog(AppConstants::LOG_CATEGORIES['EVENTS'], "🗑️ File deleted: {$exportFile->file_path}");
            }

            // Get the exportId and userId from the current exportFile instance
            $exportId = $exportFile->export_id;
            $userId = $exportFile->user_id;

            // Define the base directory path for the exportId
            $directoryPath = "exports/{$userId}/{$exportId}";

            // Check if the directory is empty after file deletion
            $files = Storage::disk('local')->files($directoryPath);

            // If no files exist in the directory, delete the directory itself
            if (empty($files)) {
                Storage::disk('local')->deleteDirectory($directoryPath);
            }
        });
    }

    /**
     * Relationship with Export model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function export()
    {
        return $this->belongsTo(Export::class, 'export_id', 'id', 'exports');
    }

    /**
     * Relationship with User model
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id', 'users');
    }

    /**
     * Get file size in KB
     */
    public function getFileSizeKbAttribute()
    {
        return round($this->file_size / 1024, 2);
    }

    /**
     * Get file size in MB
     */
    public function getFileSizeMbAttribute()
    {
        return round($this->file_size / 1048576, 2);
    }

    /**
     * Get file size in GB
     */
    public function getFileSizeGbAttribute()
    {
        return round($this->file_size / 1073741824, 4);
    }
}
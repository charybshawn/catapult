<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DataExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource',
        'filename',
        'filepath',
        'format',
        'manifest',
        'options',
        'file_size',
        'record_count',
        'user_id',
    ];

    protected $casts = [
        'manifest' => 'array',
        'options' => 'array',
    ];

    /**
     * Get the user who created this export
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the download URL for this export
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('filament.admin.data-export.download', $this);
    }

    /**
     * Check if the export file still exists
     */
    public function fileExists(): bool
    {
        return file_exists($this->filepath);
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get total records from manifest
     */
    public function getTotalRecordsAttribute(): int
    {
        if (!$this->manifest || !isset($this->manifest['statistics'])) {
            return 0;
        }
        
        return array_sum($this->manifest['statistics']);
    }

    /**
     * Delete the export file when the model is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($export) {
            if ($export->fileExists()) {
                unlink($export->filepath);
            }
        });
    }
}
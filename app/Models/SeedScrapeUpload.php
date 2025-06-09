<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeedScrapeUpload extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'original_filename', 
        'status', 
        'uploaded_at', 
        'processed_at', 
        'notes',
        'failed_entries',
        'total_entries',
        'successful_entries',
        'failed_entries_count'
    ];
    
    protected $casts = [
        'uploaded_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_entries' => 'array',
    ];
}

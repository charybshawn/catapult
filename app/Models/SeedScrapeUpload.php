<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeedScrapeUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'original_filename', 
        'status', 
        'uploaded_at', 
        'processed_at', 
        'notes'
    ];
    
    protected $casts = [
        'uploaded_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ERROR = 'error';
}

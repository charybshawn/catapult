<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeedScrapeUpload extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'filename', 
        'supplier_id',
        'uploaded_by',
        'status', 
        'uploaded_at', 
        'processed_at', 
        'notes',
        'failed_entries',
        'total_entries',
        'new_entries',
        'updated_entries',
        'successful_entries',
        'failed_entries_count'
    ];
    
    protected $casts = [
        'uploaded_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_entries' => 'array',
    ];
    
    /**
     * Get the supplier that this upload is associated with
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    
    /**
     * Get the user who uploaded this file
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

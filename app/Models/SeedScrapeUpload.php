<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents automated seed catalog data import processes for agricultural
 * supplier integration, tracking file uploads, processing status, and
 * results for microgreens seed catalog management and price monitoring.
 *
 * @business_domain Agricultural Seed Catalog Automation & Data Import
 * @workflow_context Used in supplier data integration, catalog maintenance, and price tracking
 * @agricultural_process Automates seed catalog updates from supplier data sources
 *
 * Database Table: seed_scrape_uploads
 * @property int $id Primary identifier for upload record
 * @property string $filename Original uploaded file name
 * @property int $supplier_id Reference to agricultural supplier providing data
 * @property int $uploaded_by User who initiated the upload process
 * @property string $status Processing status (pending, processing, completed, error)
 * @property Carbon $uploaded_at Timestamp when file was uploaded
 * @property Carbon|null $processed_at Timestamp when processing completed
 * @property string|null $notes Processing notes and observations
 * @property array|null $failed_entries Detailed information about failed records
 * @property int|null $total_entries Total number of records in upload file
 * @property int|null $new_entries Count of new seed entries created
 * @property int|null $updated_entries Count of existing entries updated
 * @property int|null $successful_entries Total successful processing count
 * @property int|null $failed_entries_count Number of failed processing records
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship supplier BelongsTo relationship to Supplier for data source context
 * @relationship uploadedBy BelongsTo relationship to User who initiated upload
 *
 * @business_rule Upload processing tracks success and failure rates for data quality
 * @business_rule Failed entries include detailed error information for troubleshooting
 * @business_rule Status progression: pending → processing → completed/error
 *
 * @agricultural_automation Enables bulk seed catalog updates from supplier data
 * @data_integration Supports price monitoring and catalog synchronization
 */
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
     * Get the agricultural supplier associated with this data upload.
     * Links upload data to specific supplier for seed catalog context.
     *
     * @return BelongsTo Supplier relationship
     * @agricultural_context Connects upload data to specific seed supplier
     * @business_usage Used in supplier-specific data processing and validation
     * @data_integration Enables supplier-aware catalog updates and pricing
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    
    /**
     * Get the user who initiated this seed catalog upload.
     * Tracks responsibility for agricultural data import operations.
     *
     * @return BelongsTo User relationship
     * @agricultural_context Identifies who initiated catalog automation process
     * @business_usage Used in audit trails and upload management workflows
     * @responsibility_tracking Links uploads to specific team members
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

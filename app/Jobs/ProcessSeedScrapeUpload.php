<?php

namespace App\Jobs;

use Exception;
use App\Models\SeedScrapeUpload;
use App\Services\SeedScrapeImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Background job for processing agricultural seed catalog data uploads and imports.
 * 
 * Handles comprehensive processing of scraped seed catalog data from agricultural suppliers
 * including variety information, pricing data, availability status, and growing specifications.
 * Provides automated import workflow with validation, error handling, and performance monitoring
 * for maintaining up-to-date seed catalog information in agricultural microgreens operations.
 *
 * @package App\Jobs
 * @author Catapult Development Team
 * @since 1.0.0
 * 
 * @agricultural_data Seed varieties, pricing information, supplier catalogs, growing specifications
 * @import_processing File validation, data parsing, catalog synchronization
 * @queue_management Background processing with retry logic and error recovery
 * 
 * @performance_features Memory usage monitoring, processing time tracking, file system optimization
 * @error_handling Comprehensive validation, exception logging, graceful failure recovery
 * 
 * @related_models SeedScrapeUpload, SeedEntry For seed catalog import tracking and data storage
 * @related_services SeedScrapeImporter For specialized seed data processing and validation
 */
class ProcessSeedScrapeUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Agricultural seed catalog upload instance for import processing.
     *
     * @var SeedScrapeUpload Model tracking seed catalog upload and import progress
     */
    protected $scrapeUpload;

    /**
     * File system path to agricultural seed catalog data for processing.
     *
     * @var string Absolute path to uploaded seed catalog file for import processing
     */
    protected $filePath;

    /**
     * Initialize agricultural seed catalog processing job with upload context.
     * 
     * Creates background job instance for processing scraped seed catalog data including
     * variety information, pricing details, availability status, and growing specifications.
     * Associates job with specific upload record for progress tracking and error recovery
     * in agricultural seed catalog management workflows.
     *
     * @param SeedScrapeUpload $scrapeUpload Agricultural seed upload record for processing tracking
     * @param string $filePath File system path to seed catalog data for import processing
     * @return void Initializes job for background seed catalog processing
     * 
     * @agricultural_context Seed varieties, supplier catalogs, pricing data, growing specifications
     * @import_workflow File-based seed catalog import with progress tracking
     */
    public function __construct(SeedScrapeUpload $scrapeUpload, string $filePath)
    {
        $this->scrapeUpload = $scrapeUpload;
        $this->filePath = $filePath;
    }

    /**
     * Execute comprehensive agricultural seed catalog import and processing workflow.
     * 
     * Processes uploaded seed catalog data through complete validation, parsing, and import
     * pipeline including file system validation, data structure verification, agricultural
     * catalog synchronization, and performance monitoring. Provides comprehensive error
     * handling and recovery for reliable seed catalog management in agricultural operations.
     *
     * @return void Processes seed catalog import with status tracking and error handling
     * 
     * @throws Exception Comprehensive error handling with detailed agricultural context logging
     * 
     * @import_pipeline File validation, data parsing, catalog synchronization, cleanup
     * @agricultural_processing Seed varieties, pricing updates, availability synchronization
     * @performance_monitoring Memory usage, processing time, file system optimization
     */
    public function handle(): void
    {
        Log::info('ProcessSeedScrapeUpload: Starting job execution', [
            'job_id' => $this->job->getJobId(),
            'upload_id' => $this->scrapeUpload->id, 
            'file_name' => $this->scrapeUpload->filename,
            'file_path' => $this->filePath,
            'file_exists' => file_exists($this->filePath),
            'file_size' => file_exists($this->filePath) ? filesize($this->filePath) : 0,
            'file_permissions' => file_exists($this->filePath) ? decoct(fileperms($this->filePath)) : null,
            'queue' => $this->queue,
            'attempts' => $this->attempts(),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024 . ' MB'
        ]);

        // Update status to processing
        $this->scrapeUpload->update(['status' => SeedScrapeUpload::STATUS_PROCESSING]);
        
        Log::debug('ProcessSeedScrapeUpload: File system details', [
            'upload_id' => $this->scrapeUpload->id,
            'file_path' => $this->filePath,
            'real_path' => file_exists($this->filePath) ? realpath($this->filePath) : null,
            'directory' => dirname($this->filePath),
            'directory_exists' => is_dir(dirname($this->filePath)),
            'is_readable' => is_readable($this->filePath),
            'is_writable' => is_writable($this->filePath),
            'temp_dir' => sys_get_temp_dir(),
            'upload_tmp_dir' => ini_get('upload_tmp_dir')
        ]);

        try {
            // Validate file before processing
            if (!file_exists($this->filePath)) {
                throw new Exception("File not found at path: {$this->filePath}");
            }
            
            if (!is_readable($this->filePath)) {
                throw new Exception("File is not readable: {$this->filePath}");
            }
            
            $fileSize = filesize($this->filePath);
            if ($fileSize === 0) {
                throw new Exception("File is empty: {$this->filePath}");
            }
            
            Log::info('ProcessSeedScrapeUpload: File validation passed', [
                'upload_id' => $this->scrapeUpload->id,
                'file_size' => $fileSize,
                'mime_type' => mime_content_type($this->filePath)
            ]);
            
            // Create importer and process
            Log::info('ProcessSeedScrapeUpload: Initializing SeedScrapeImporter', [
                'upload_id' => $this->scrapeUpload->id
            ]);
            
            $importer = new SeedScrapeImporter();
            $importer->import($this->filePath, $this->scrapeUpload);
            
            // Refresh the model to get updated stats
            $this->scrapeUpload->refresh();
            
            Log::info('ProcessSeedScrapeUpload: Job completed successfully', [
                'upload_id' => $this->scrapeUpload->id,
                'final_status' => $this->scrapeUpload->status,
                'total_entries' => $this->scrapeUpload->total_entries,
                'successful_entries' => $this->scrapeUpload->successful_entries,
                'failed_entries' => $this->scrapeUpload->failed_entries_count,
                'processing_time' => $this->scrapeUpload->processed_at ? 
                    $this->scrapeUpload->processed_at->diffInSeconds($this->scrapeUpload->uploaded_at) . ' seconds' : null,
                'memory_peak_usage' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB'
            ]);
            
            // Clean up temp file if it exists
            if (file_exists($this->filePath) && strpos($this->filePath, sys_get_temp_dir()) === 0) {
                Log::info('ProcessSeedScrapeUpload: Cleaning up temp file', [
                    'upload_id' => $this->scrapeUpload->id,
                    'file_path' => $this->filePath
                ]);
                unlink($this->filePath);
            }
            
        } catch (Exception $e) {
            Log::error('ProcessSeedScrapeUpload: Job failed with exception', [
                'upload_id' => $this->scrapeUpload->id,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'file_path' => $this->filePath,
                'attempts' => $this->attempts(),
                'max_attempts' => $this->tries ?? 3
            ]);
            
            // Update upload status
            $this->scrapeUpload->update([
                'status' => SeedScrapeUpload::STATUS_ERROR,
                'notes' => 'Job failed: ' . $e->getMessage()
            ]);
            
            $this->fail($e);
        }
    }
}

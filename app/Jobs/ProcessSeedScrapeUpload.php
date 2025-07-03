<?php

namespace App\Jobs;

use App\Models\SeedScrapeUpload;
use App\Services\SeedScrapeImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSeedScrapeUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The scrape upload instance.
     *
     * @var \App\Models\SeedScrapeUpload
     */
    protected $scrapeUpload;

    /**
     * The file path to process.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Create a new job instance.
     *
     * @param  SeedScrapeUpload  $scrapeUpload
     * @param  string  $filePath
     * @return void
     */
    public function __construct(SeedScrapeUpload $scrapeUpload, string $filePath)
    {
        $this->scrapeUpload = $scrapeUpload;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
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
                throw new \Exception("File not found at path: {$this->filePath}");
            }
            
            if (!is_readable($this->filePath)) {
                throw new \Exception("File is not readable: {$this->filePath}");
            }
            
            $fileSize = filesize($this->filePath);
            if ($fileSize === 0) {
                throw new \Exception("File is empty: {$this->filePath}");
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
            
        } catch (\Exception $e) {
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

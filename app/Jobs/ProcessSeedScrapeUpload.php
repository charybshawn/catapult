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
        Log::info('Starting seed scrape upload processing job', [
            'upload_id' => $this->scrapeUpload->id, 
            'file_name' => $this->scrapeUpload->original_filename
        ]);

        try {
            $importer = new SeedScrapeImporter();
            $importer->import($this->filePath, $this->scrapeUpload);
            
            Log::info('Seed scrape upload processing completed', [
                'upload_id' => $this->scrapeUpload->id
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing seed scrape upload', [
                'upload_id' => $this->scrapeUpload->id,
                'error' => $e->getMessage()
            ]);
            
            $this->fail($e);
        }
    }
}

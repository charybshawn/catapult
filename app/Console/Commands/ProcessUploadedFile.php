<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSeedScrapeUpload;
use App\Models\SeedScrapeUpload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedFile extends Command
{
    protected $signature = 'seed:process-upload {filename}';
    protected $description = 'Process an uploaded seed data file';

    public function handle()
    {
        $filename = $this->argument('filename');
        $fullPath = 'seed-scrape-uploads/' . $filename;
        
        $this->info("Looking for file at: {$fullPath}");
        $this->info("Absolute path: " . Storage::disk('local')->path($fullPath));
        
        // Check if file exists
        if (!Storage::disk('local')->exists($fullPath)) {
            $this->error("File not found at: {$fullPath}");
            
            // List all files in the directory
            $this->info("Available files in seed-scrape-uploads:");
            $files = Storage::disk('local')->files('seed-scrape-uploads');
            foreach ($files as $file) {
                $this->info("- {$file}");
            }
            
            return Command::FAILURE;
        }
        
        $this->info('Creating seed scrape upload record...');
        
        $upload = SeedScrapeUpload::create([
            'filename' => $filename,
            'status' => SeedScrapeUpload::STATUS_PENDING,
            'uploaded_at' => now(),
        ]);
        
        $this->info('Created upload record with ID: ' . $upload->id);
        
        $filePath = Storage::disk('local')->path($fullPath);
        
        $this->info('Dispatching process job for file: ' . $filePath);
        
        // Process synchronously for testing
        try {
            $importer = new \App\Services\SeedScrapeImporter();
            $importer->import($filePath, $upload);
            
            $this->info('Successfully processed file!');
            $this->info('Status: ' . $upload->fresh()->status);
            $this->info('Notes: ' . $upload->fresh()->notes);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error processing file: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 
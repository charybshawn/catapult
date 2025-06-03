<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSeedScrapeUpload;
use App\Models\SeedScrapeUpload;
use Illuminate\Console\Command;

class TestSeedScrapeImport extends Command
{
    protected $signature = 'test:seed-import';
    protected $description = 'Test the seed scrape import process';

    public function handle()
    {
        $this->info('Creating test seed scrape upload record...');
        
        $upload = SeedScrapeUpload::create([
            'original_filename' => 'test_seed_data.json',
            'status' => SeedScrapeUpload::STATUS_PENDING,
            'uploaded_at' => now(),
        ]);
        
        $this->info('Created upload record with ID: ' . $upload->id);
        
        $this->info('Processing test file synchronously...');
        
        try {
            $importer = new \App\Services\SeedScrapeImporter();
            $importer->import(storage_path('app/test_seed_data.json'), $upload);
            
            $this->info('Successfully processed test file!');
            $this->info('Status: ' . $upload->fresh()->status);
            $this->info('Notes: ' . $upload->fresh()->notes);
        } catch (\Exception $e) {
            $this->error('Error processing test file: ' . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
} 
<?php

namespace App\Console\Commands;

use App\Models\SeedCultivar;
use App\Models\SeedEntry;
use App\Models\SeedPriceHistory;
use App\Models\SeedScrapeUpload;
use App\Models\SeedVariation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearSeedTestData extends Command
{
    protected $signature = 'test:clear-seed-data';
    protected $description = 'Clear all test seed data from the database';

    public function handle()
    {
        $this->info('Clearing all seed test data...');
        
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        try {
            // Delete in proper order to avoid foreign key constraints
            $priceHistoryCount = SeedPriceHistory::count();
            SeedPriceHistory::query()->delete();
            $this->info("Deleted {$priceHistoryCount} price history records");
            
            $variationCount = SeedVariation::count();
            SeedVariation::query()->delete();
            $this->info("Deleted {$variationCount} variations");
            
            $entryCount = SeedEntry::count();
            SeedEntry::query()->delete();
            $this->info("Deleted {$entryCount} entries");
            
            $cultivarCount = SeedCultivar::count();
            SeedCultivar::query()->delete();
            $this->info("Deleted {$cultivarCount} cultivars");
            
            $uploadCount = SeedScrapeUpload::count();
            SeedScrapeUpload::query()->delete();
            $this->info("Deleted {$uploadCount} upload records");
            
            $this->info('All seed test data has been cleared');
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        
        return Command::SUCCESS;
    }
} 
<?php

namespace App\Console\Commands;

use App\Models\SeedCultivar;
use App\Models\SeedEntry;
use App\Models\SeedPriceHistory;
use App\Models\SeedVariation;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestImporterDirect extends Command
{
    protected $signature = 'test:importer-direct';
    protected $description = 'Test the seed variation and price history creation directly';

    public function handle()
    {
        $this->info('Creating test data directly...');
        
        // Get or create a supplier
        $supplier = Supplier::firstOrCreate(['name' => 'Test Supplier']);
        
        // Get or create a cultivar
        $cultivar = SeedCultivar::firstOrCreate(['name' => 'Test Cultivar']);
        
        // Create a seed entry
        $entry = SeedEntry::firstOrCreate(
            [
                'supplier_id' => $supplier->id,
                'supplier_product_url' => 'https://example.com/test',
            ],
            [
                'seed_cultivar_id' => $cultivar->id,
                'supplier_product_title' => 'Test Product',
                'image_url' => null,
                'description' => null,
                'tags' => [],
            ]
        );
        
        // Check if this is a new variation
        $sizeDescription = 'Test Size';
        $exists = SeedVariation::where('seed_entry_id', $entry->id)
            ->where('size_description', $sizeDescription)
            ->exists();
        $isNewVariation = !$exists;
        
        $this->info('Is new variation: ' . ($isNewVariation ? 'Yes' : 'No'));
        
        // Create the variation
        $variation = SeedVariation::firstOrCreate(
            [
                'seed_entry_id' => $entry->id,
                'size_description' => $sizeDescription,
            ],
            [
                'sku' => 'TEST-123',
                'weight_kg' => 0.1,
                'original_weight_value' => '100',
                'original_weight_unit' => 'g',
                'current_price' => 5.99,
                'currency' => 'USD',
                'is_in_stock' => true,
                'last_checked_at' => now(),
            ]
        );
        
        $this->info('Variation created with ID: ' . $variation->id);
        
        // Create price history
        $history = new SeedPriceHistory();
        $history->seed_variation_id = $variation->id;
        $history->price = 5.99;
        $history->currency = 'USD';
        $history->is_in_stock = true;
        $history->scraped_at = now();
        $result = $history->save();
        
        $this->info('Price history created: ' . ($result ? 'Success' : 'Failure'));
        
        // Check the current price history records
        $count = SeedPriceHistory::count();
        $this->info('Total price history records: ' . $count);
        
        return Command::SUCCESS;
    }
} 
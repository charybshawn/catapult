<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\SeedCultivar;
use App\Models\SeedEntry;
use App\Models\SeedPriceHistory;
use App\Models\SeedScrapeUpload;
use App\Models\SeedVariation;
use App\Models\Supplier;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class SeedScrapeImporter
{
    /**
     * Import seed data from a JSON file
     *
     * @param string $jsonFilePath Path to the JSON file
     * @param SeedScrapeUpload $scrapeUpload The upload record
     * @return void
     * @throws Exception
     */
    public function import(string $jsonFilePath, SeedScrapeUpload $scrapeUpload): void
    {
        try {
            $jsonData = json_decode(file_get_contents($jsonFilePath), true);
            
            if (!isset($jsonData['data']) || !is_array($jsonData['data'])) {
                throw new Exception("Invalid JSON format: 'data' array not found");
            }
            
            Log::info('Beginning seed data import', [
                'file' => $scrapeUpload->original_filename,
                'product_count' => count($jsonData['data'])
            ]);
            
            // Update status to processing
            $scrapeUpload->update([
                'status' => SeedScrapeUpload::STATUS_PROCESSING
            ]);
            
            // Extract supplier information from the data
            $supplierName = $jsonData['source_site'] ?? 'Unknown Supplier';
            $supplier = Supplier::firstOrCreate(['name' => $supplierName]);
            
            // Process each product
            foreach ($jsonData['data'] as $productData) {
                $this->processProduct($productData, $supplier, $jsonData['timestamp'] ?? now()->toIso8601String());
            }
            
            // Update the scrape upload record
            $scrapeUpload->update([
                'status' => SeedScrapeUpload::STATUS_COMPLETED,
                'processed_at' => now(),
                'notes' => 'Successfully processed ' . count($jsonData['data']) . ' products.'
            ]);
            
            Log::info('Seed data import completed successfully', [
                'file' => $scrapeUpload->original_filename
            ]);
            
        } catch (Exception $e) {
            Log::error('Error importing seed data', [
                'file' => $scrapeUpload->original_filename,
                'error' => $e->getMessage()
            ]);
            
            // Handle any exceptions
            $scrapeUpload->update([
                'status' => SeedScrapeUpload::STATUS_ERROR,
                'processed_at' => now(),
                'notes' => 'Error: ' . $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Process a single product from the JSON data
     *
     * @param array $productData
     * @param Supplier $supplier
     * @param string $timestamp
     * @return void
     */
    protected function processProduct(array $productData, Supplier $supplier, string $timestamp): void
    {
        // Extract cultivar name from the title
        $cultivarName = $productData['cultivar'] ?? 'Unknown Cultivar';
        $seedCultivar = SeedCultivar::firstOrCreate(['name' => $cultivarName]);
        
        // Find or create the seed entry
        $seedEntry = SeedEntry::firstOrCreate(
            [
                'supplier_id' => $supplier->id,
                'supplier_product_url' => $productData['url'] ?? '',
            ],
            [
                'seed_cultivar_id' => $seedCultivar->id,
                'supplier_product_title' => $productData['title'] ?? 'Unknown Product',
                'image_url' => $productData['image_url'] ?? null,
                'description' => $productData['description'] ?? null,
                'tags' => $productData['tags'] ?? [],
            ]
        );
        
        Log::debug('Processing seed entry', [
            'cultivar' => $seedCultivar->name,
            'supplier' => $supplier->name,
            'title' => $seedEntry->supplier_product_title
        ]);
        
        // Process each variant
        if (isset($productData['variants']) && is_array($productData['variants'])) {
            foreach ($productData['variants'] as $variantData) {
                $this->processVariant($variantData, $seedEntry, $timestamp);
            }
        }
    }
    
    /**
     * Process a single variant from the product data
     *
     * @param array $variantData
     * @param SeedEntry $seedEntry
     * @param string $timestamp
     * @return void
     */
    protected function processVariant(array $variantData, SeedEntry $seedEntry, string $timestamp): void
    {
        $sizeDescription = $variantData['variant_title'] ?? 'Default';
        
        Log::debug('Processing seed variation', [
            'entry_id' => $seedEntry->id,
            'size' => $sizeDescription,
            'price' => $variantData['price'] ?? 0
        ]);
        
        // Find or create the seed variation
        $variation = SeedVariation::firstOrCreate(
            [
                'seed_entry_id' => $seedEntry->id,
                'size_description' => $sizeDescription,
            ],
            [
                'sku' => $variantData['sku'] ?? null,
                'weight_kg' => $variantData['weight_kg'] ?? null,
                'original_weight_value' => $variantData['original_weight_value'] ?? null,
                'original_weight_unit' => $variantData['original_weight_unit'] ?? null,
                'current_price' => $variantData['price'] ?? 0,
                'currency' => $variantData['currency'] ?? 'USD',
                'is_in_stock' => $variantData['is_variant_in_stock'] ?? false,
                'last_checked_at' => now(),
            ]
        );
        
        // Update the variation with the latest data
        $priceChanged = $variation->current_price != ($variantData['price'] ?? 0);
        $stockChanged = $variation->is_in_stock != ($variantData['is_variant_in_stock'] ?? false);
        
        $variation->update([
            'current_price' => $variantData['price'] ?? $variation->current_price,
            'currency' => $variantData['currency'] ?? $variation->currency,
            'is_in_stock' => $variantData['is_variant_in_stock'] ?? $variation->is_in_stock,
            'last_checked_at' => now(),
        ]);
        
        // Create a price history record only if price or stock status changed
        if ($priceChanged || $stockChanged) {
            SeedPriceHistory::create([
                'seed_variation_id' => $variation->id,
                'price' => $variantData['price'] ?? $variation->current_price,
                'currency' => $variantData['currency'] ?? $variation->currency,
                'is_in_stock' => $variantData['is_variant_in_stock'] ?? $variation->is_in_stock,
                'scraped_at' => Carbon::parse($timestamp),
            ]);
            
            Log::info('Created price history record', [
                'variation_id' => $variation->id,
                'price' => $variantData['price'] ?? $variation->current_price,
                'price_changed' => $priceChanged,
                'stock_changed' => $stockChanged
            ]);
        }
        
        // Check if we need to create or update a consumable entry
        $this->syncWithConsumableInventory($variation, $seedEntry);
    }
    
    /**
     * Sync the seed variation with the consumable inventory system
     *
     * @param SeedVariation $variation
     * @param SeedEntry $seedEntry
     * @return void
     */
    protected function syncWithConsumableInventory(SeedVariation $variation, SeedEntry $seedEntry): void
    {
        // Skip if already linked to a consumable
        if ($variation->consumable_id) {
            return;
        }
        
        // Try to find an existing consumable with matching details
        $consumable = Consumable::where('type', 'seed')
            ->where('name', 'LIKE', '%' . $seedEntry->seedCultivar->name . '%')
            ->where(function($query) use ($variation) {
                $query->where('sku', $variation->sku)
                    ->orWhere(function($q) use ($variation) {
                        // If no SKU, try to match by weight
                        return $q->whereNull('sku')
                            ->where('weight_kg', $variation->weight_kg);
                    });
            })
            ->first();
            
        if (!$consumable) {
            // Create a new consumable entry if one doesn't exist
            $consumable = Consumable::create([
                'type' => 'seed',
                'name' => $seedEntry->seedCultivar->name . ' - ' . $variation->size_description,
                'sku' => $variation->sku,
                'quantity' => 0, // Start with 0 since we don't know the actual inventory
                'restock_level' => 1, // Default restock level
                'weight_kg' => $variation->weight_kg,
                'supplier_id' => $seedEntry->supplier_id,
                // Add other required fields based on your Consumable model
            ]);
            
            Log::info('Created new consumable for seed variation', [
                'consumable_id' => $consumable->id,
                'variation_id' => $variation->id,
                'name' => $consumable->name
            ]);
        }
        
        // Link the variation to the consumable
        $variation->update([
            'consumable_id' => $consumable->id
        ]);
        
        Log::info('Linked seed variation to consumable', [
            'variation_id' => $variation->id,
            'consumable_id' => $consumable->id
        ]);
    }
} 
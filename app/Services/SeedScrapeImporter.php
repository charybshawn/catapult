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
            
            // Get currency code from top level if available
            $currencyCode = $jsonData['currency_code'] ?? 'USD';
            
            // Process each product
            foreach ($jsonData['data'] as $productData) {
                $this->processProduct($productData, $supplier, $jsonData['timestamp'] ?? now()->toIso8601String(), $currencyCode);
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
     * @param string $defaultCurrency
     * @return void
     */
    protected function processProduct(array $productData, Supplier $supplier, string $timestamp, string $defaultCurrency = 'USD'): void
    {
        // Extract cultivar name - try different field combinations to support different formats
        $cultivarName = 'Unknown Cultivar';
        
        // First, check if we have a dedicated cultivar_name field
        if (isset($productData['cultivar_name']) && $productData['cultivar_name'] !== 'N/A') {
            $cultivarName = $productData['cultivar_name'];
        } 
        // If not, check if we have a cultivar field (older format)
        elseif (isset($productData['cultivar']) && !empty($productData['cultivar'])) {
            $cultivarName = $productData['cultivar'];
        }
        // As a fallback, try to extract from common_name
        elseif (isset($productData['common_name']) && !empty($productData['common_name'])) {
            $cultivarName = $productData['common_name'];
        }
        
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
            'title' => $seedEntry->supplier_product_title,
            'product_stock_status' => $productData['is_in_stock'] ?? 'Unknown'
        ]);
        
        // Process each variant - check both 'variants' and 'variations' keys
        // Germina.ca uses 'variations', other sites might use 'variants'
        $variantsArray = null;
        if (isset($productData['variations']) && is_array($productData['variations'])) {
            $variantsArray = $productData['variations'];
        } elseif (isset($productData['variants']) && is_array($productData['variants'])) {
            $variantsArray = $productData['variants'];
        }
        
        if ($variantsArray) {
            foreach ($variantsArray as $variantData) {
                $this->processVariant($variantData, $seedEntry, $timestamp, $defaultCurrency, $productData['is_in_stock'] ?? true);
            }
        } else {
            Log::warning('No variants/variations found for product', [
                'title' => $seedEntry->supplier_product_title,
                'url' => $productData['url'] ?? 'No URL'
            ]);
        }
    }
    
    /**
     * Process a single variant from the product data
     *
     * @param array $variantData
     * @param SeedEntry $seedEntry
     * @param string $timestamp
     * @param string $defaultCurrency
     * @param bool $productIsInStock Global product stock status
     * @return void
     */
    protected function processVariant(array $variantData, SeedEntry $seedEntry, string $timestamp, string $defaultCurrency = 'USD', bool $productIsInStock = true): void
    {
        // Field mapping for different JSON structures
        // Different sites use different field names for size/variant title
        $sizeDescription = null;
        
        // Check for different possible size field names
        if (isset($variantData['size']) && !empty($variantData['size'])) {
            $sizeDescription = $variantData['size'];
        } elseif (isset($variantData['variant_title']) && !empty($variantData['variant_title'])) {
            $sizeDescription = $variantData['variant_title'];
        } elseif (isset($variantData['title']) && !empty($variantData['title'])) {
            $sizeDescription = $variantData['title'];
        } else {
            $sizeDescription = 'Default';
        }
        
        Log::debug('Processing seed variation', [
            'entry_id' => $seedEntry->id,
            'size' => $sizeDescription,
            'price' => $variantData['price'] ?? 0
        ]);
        
        // Check if this is a new variation
        $exists = SeedVariation::where('seed_entry_id', $seedEntry->id)
            ->where('size_description', $sizeDescription)
            ->exists();
        $isNewVariation = !$exists;
        
        // Get price from variants
        $price = null;
        if (isset($variantData['price']) && is_numeric($variantData['price'])) {
            $price = floatval($variantData['price']);
        }
        
        // Get currency from variants or use default
        $currency = $variantData['currency'] ?? $defaultCurrency;
        
        // Get stock status - Various sites use different field names
        // Check variation level stock status first, then fallback to product level
        $isInStock = false;
        
        // Check all possible variation-level stock status field names
        if (isset($variantData['is_variation_in_stock'])) {
            $isInStock = (bool)$variantData['is_variation_in_stock'];
        } elseif (isset($variantData['is_variant_in_stock'])) {
            $isInStock = (bool)$variantData['is_variant_in_stock'];
        } elseif (isset($variantData['is_in_stock'])) {
            $isInStock = (bool)$variantData['is_in_stock']; 
        } else {
            // If no variation-level stock status, use product-level
            $isInStock = $productIsInStock;
        }
        
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
                'current_price' => $price ?? 0,
                'currency' => $currency,
                'is_in_stock' => $isInStock,
                'last_checked_at' => now(),
            ]
        );
        
        // Check if price or stock has changed
        $priceChanged = false;
        if ($price !== null) {
            $priceChanged = abs($variation->current_price - $price) > 0.001;
        }
        
        $stockChanged = $variation->is_in_stock !== $isInStock;
        
        // Update the variation with the latest data
        if ($price !== null || $stockChanged) {
            $updateData = [];
            
            if ($price !== null) {
                $updateData['current_price'] = $price;
            }
            
            $updateData['currency'] = $currency;
            $updateData['is_in_stock'] = $isInStock;
            $updateData['last_checked_at'] = now();
            
            $variation->update($updateData);
            
            Log::debug('Updated variation', [
                'variation_id' => $variation->id,
                'price' => $price,
                'is_in_stock' => $isInStock,
                'price_changed' => $priceChanged,
                'stock_changed' => $stockChanged
            ]);
        }
        
        // Create a price history record if:
        // 1. This is a new variation, OR
        // 2. Price or stock status changed
        if ($isNewVariation || $priceChanged || $stockChanged) {
            SeedPriceHistory::create([
                'seed_variation_id' => $variation->id,
                'price' => $price ?? $variation->current_price,
                'currency' => $currency,
                'is_in_stock' => $isInStock,
                'scraped_at' => Carbon::parse($timestamp),
            ]);
            
            Log::info('Created price history record', [
                'variation_id' => $variation->id,
                'price' => $price ?? $variation->current_price,
                'is_new_variation' => $isNewVariation,
                'price_changed' => $priceChanged,
                'stock_changed' => $stockChanged
            ]);
        }
        
        // Disabled consumable integration to prevent SQL errors
        // $this->syncWithConsumableInventory($variation, $seedEntry);
    }
    
    /**
     * Sync the seed variation with the consumable inventory system
     * 
     * DISABLED: This method is currently disabled to prevent SQL errors
     * with the consumables table. Will be implemented in a future update.
     *
     * @param SeedVariation $variation
     * @param SeedEntry $seedEntry
     * @return void
     */
    protected function syncWithConsumableInventory(SeedVariation $variation, SeedEntry $seedEntry): void
    {
        // Method disabled to prevent SQL errors
        Log::info('Consumable integration disabled for seed variation', [
            'variation_id' => $variation->id,
            'cultivar' => $seedEntry->seedCultivar->name
        ]);
        
        return;
    }
} 
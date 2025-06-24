<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\SeedEntry;
use App\Models\SeedPriceHistory;
use App\Models\SeedScrapeUpload;
use App\Models\SeedVariation;
use App\Models\Supplier;
use App\Models\SupplierSourceMapping;
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
            
            $totalEntries = count($jsonData['data']);
            $successfulEntries = 0;
            $failedEntries = [];
            
            Log::info('Beginning seed data import', [
                'file' => $scrapeUpload->original_filename,
                'product_count' => $totalEntries
            ]);
            
            // Update status to processing
            $scrapeUpload->update([
                'status' => SeedScrapeUpload::STATUS_PROCESSING,
                'total_entries' => $totalEntries
            ]);
            
            // Extract supplier information from the data
            $sourceUrl = $jsonData['source_site'] ?? 'Unknown Supplier';
            
            // Check for existing mapping first
            $existingMapping = SupplierSourceMapping::findMappingForSource($sourceUrl);
            
            if ($existingMapping) {
                $supplier = $existingMapping->supplier;
                Log::info('Using existing supplier mapping', [
                    'source_url' => $sourceUrl,
                    'supplier' => $supplier->name,
                    'mapping_id' => $existingMapping->id
                ]);
            } else {
                // Fallback to old behavior for backward compatibility
                $supplier = Supplier::firstOrCreate(['name' => $sourceUrl]);
                
                // Create mapping for future use
                if ($sourceUrl !== 'Unknown Supplier') {
                    SupplierSourceMapping::createMapping(
                        $sourceUrl,
                        $supplier->id,
                        ['import_method' => 'legacy_auto_created']
                    );
                    
                    Log::info('SeedScrapeImporter: Created new supplier mapping (legacy mode)', [
                        'upload_id' => $scrapeUpload->id,
                        'source_url' => $sourceUrl,
                        'supplier_id' => $supplier->id,
                        'supplier_name' => $supplier->name
                    ]);
                }
            }
            
            // Get currency code from top level if available, or detect from supplier
            $currencyCode = $this->detectCurrency($jsonData, $supplier->name);
            
            Log::info('SeedScrapeImporter: Detected currency and starting product processing', [
                'upload_id' => $scrapeUpload->id,
                'currency_code' => $currencyCode,
                'supplier_id' => $supplier->id,
                'total_products' => count($jsonData['data'])
            ]);
            
            // Process each product
            foreach ($jsonData['data'] as $index => $productData) {
                Log::debug('SeedScrapeImporter: Processing product', [
                    'upload_id' => $scrapeUpload->id,
                    'product_index' => $index,
                    'product_title' => $productData['title'] ?? 'Unknown',
                    'product_url' => $productData['url'] ?? 'No URL',
                    'has_variations' => isset($productData['variations']) || isset($productData['variants'])
                ]);
                try {
                    $this->processProduct($productData, $supplier, $jsonData['timestamp'] ?? now()->toIso8601String(), $currencyCode);
                    $successfulEntries++;
                } catch (Exception $e) {
                    // Capture failed entry with context
                    $failedEntry = [
                        'index' => $index,
                        'data' => $productData,
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'timestamp' => now()->toIso8601String()
                    ];
                    
                    $failedEntries[] = $failedEntry;
                    
                    Log::warning('Failed to process product entry', [
                        'index' => $index,
                        'title' => $productData['title'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Determine final status based on results
            $finalStatus = SeedScrapeUpload::STATUS_COMPLETED;
            $notes = "Processed {$successfulEntries}/{$totalEntries} products successfully.";
            
            if ($failedEntries) {
                $failedCount = count($failedEntries);
                if ($successfulEntries === 0) {
                    $finalStatus = SeedScrapeUpload::STATUS_ERROR;
                    $notes = "All entries failed to process. {$failedCount} errors encountered.";
                } else {
                    $notes .= " {$failedCount} entries failed to process.";
                }
            }
            
            // Update the scrape upload record
            $scrapeUpload->update([
                'status' => $finalStatus,
                'processed_at' => now(),
                'notes' => $notes,
                'successful_entries' => $successfulEntries,
                'failed_entries_count' => count($failedEntries),
                'failed_entries' => $failedEntries
            ]);
            
            Log::info('Seed data import completed', [
                'file' => $scrapeUpload->original_filename,
                'successful' => $successfulEntries,
                'failed' => count($failedEntries),
                'status' => $finalStatus
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
                'notes' => 'Error: ' . $e->getMessage(),
                'total_entries' => $totalEntries ?? 0,
                'successful_entries' => 0,
                'failed_entries_count' => 0
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Import seed data from a JSON file with a pre-selected supplier
     * This method bypasses supplier detection and uses the provided supplier
     *
     * @param string $jsonFilePath Path to the JSON file
     * @param SeedScrapeUpload $scrapeUpload The upload record
     * @param Supplier $supplier The pre-selected supplier
     * @return void
     * @throws Exception
     */
    public function importWithSupplier(string $jsonFilePath, SeedScrapeUpload $scrapeUpload, Supplier $supplier): void
    {
        try {
            $jsonData = json_decode(file_get_contents($jsonFilePath), true);
            
            if (!isset($jsonData['data']) || !is_array($jsonData['data'])) {
                throw new Exception("Invalid JSON format: 'data' array not found");
            }
            
            $totalEntries = count($jsonData['data']);
            $successfulEntries = 0;
            $failedEntries = [];
            
            Log::info('Beginning seed data import with pre-selected supplier', [
                'file' => $scrapeUpload->original_filename,
                'supplier' => $supplier->name,
                'supplier_id' => $supplier->id,
                'product_count' => $totalEntries
            ]);
            
            // Update status to processing
            $scrapeUpload->update([
                'status' => SeedScrapeUpload::STATUS_PROCESSING,
                'total_entries' => $totalEntries
            ]);
            
            // Create/update supplier mapping if source_site is present
            if (isset($jsonData['source_site'])) {
                SupplierSourceMapping::createMapping(
                    $jsonData['source_site'],
                    $supplier->id,
                    [
                        'import_method' => 'pre_selected',
                        'import_file' => $scrapeUpload->original_filename,
                        'created_at' => now()->toISOString()
                    ]
                );
                
                Log::info('Created/updated supplier mapping', [
                    'source_url' => $jsonData['source_site'],
                    'supplier' => $supplier->name
                ]);
            }
            
            // Get currency code from top level if available, or detect from supplier
            $currencyCode = $this->detectCurrency($jsonData, $supplier->name);
            
            // Process each product
            foreach ($jsonData['data'] as $index => $productData) {
                try {
                    $this->processProduct($productData, $supplier, $jsonData['timestamp'] ?? now()->toIso8601String(), $currencyCode);
                    $successfulEntries++;
                } catch (Exception $e) {
                    // Capture failed entry with context
                    $failedEntry = [
                        'index' => $index,
                        'data' => $productData,
                        'error' => $e->getMessage(),
                        'error_type' => get_class($e),
                        'timestamp' => now()->toIso8601String()
                    ];
                    
                    $failedEntries[] = $failedEntry;
                    
                    Log::warning('Failed to process product entry', [
                        'index' => $index,
                        'title' => $productData['title'] ?? 'Unknown',
                        'supplier' => $supplier->name,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Determine final status based on results
            $finalStatus = SeedScrapeUpload::STATUS_COMPLETED;
            $notes = "Processed {$successfulEntries}/{$totalEntries} products successfully with supplier: {$supplier->name}.";
            
            if ($failedEntries) {
                $failedCount = count($failedEntries);
                if ($successfulEntries === 0) {
                    $finalStatus = SeedScrapeUpload::STATUS_ERROR;
                    $notes = "All entries failed to process with supplier: {$supplier->name}. {$failedCount} errors encountered.";
                } else {
                    $notes .= " {$failedCount} entries failed to process.";
                }
            }
            
            // Update the scrape upload record
            $scrapeUpload->update([
                'status' => $finalStatus,
                'processed_at' => now(),
                'notes' => $notes,
                'successful_entries' => $successfulEntries,
                'failed_entries_count' => count($failedEntries),
                'failed_entries' => $failedEntries
            ]);
            
            Log::info('Seed data import completed with pre-selected supplier', [
                'file' => $scrapeUpload->original_filename,
                'supplier' => $supplier->name,
                'successful' => $successfulEntries,
                'failed' => count($failedEntries),
                'status' => $finalStatus
            ]);
            
        } catch (Exception $e) {
            Log::error('Error importing seed data with pre-selected supplier', [
                'file' => $scrapeUpload->original_filename,
                'supplier' => $supplier->name ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            
            // Handle any exceptions
            $scrapeUpload->update([
                'status' => SeedScrapeUpload::STATUS_ERROR,
                'processed_at' => now(),
                'notes' => 'Error with supplier ' . $supplier->name . ': ' . $e->getMessage(),
                'total_entries' => $totalEntries ?? 0,
                'successful_entries' => 0,
                'failed_entries_count' => 0
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
    public function processProduct(array $productData, Supplier $supplier, string $timestamp, string $defaultCurrency = 'USD'): void
    {
        // Extract cultivar name and common name - try different field combinations to support different formats
        $cultivarName = $this->extractCultivarName($productData);
        $commonName = $this->extractCommonNameFromProductData($productData);
        
        // First check for existing seed entry with same common_name, cultivar_name, and supplier
        // This prevents creating duplicates that would later need to be merged
        $seedEntry = SeedEntry::where('supplier_id', $supplier->id)
            ->where('common_name', $commonName)
            ->where('cultivar_name', $cultivarName)
            ->first();
            
        if ($seedEntry) {
            // Update existing entry with best available data (only if current data is empty/missing)
            $updateData = [];
            
            if (empty($seedEntry->supplier_product_title) && !empty($productData['title'])) {
                $updateData['supplier_product_title'] = $productData['title'];
            }
            
            if (empty($seedEntry->supplier_product_url) && !empty($productData['url'])) {
                $updateData['supplier_product_url'] = $productData['url'];
            }
            
            if (empty($seedEntry->image_url) && !empty($productData['image_url'])) {
                $updateData['image_url'] = $productData['image_url'];
            }
            
            if (empty($seedEntry->description) && !empty($productData['description'])) {
                $updateData['description'] = $productData['description'];
            }
            
            // Merge tags
            if (!empty($productData['tags'])) {
                $existingTags = $seedEntry->tags ?? [];
                $newTags = array_unique(array_merge($existingTags, $productData['tags']));
                $updateData['tags'] = $newTags;
            }
            
            if (!empty($updateData)) {
                $seedEntry->update($updateData);
                Log::info('Updated existing seed entry with additional data', [
                    'entry_id' => $seedEntry->id,
                    'updated_fields' => array_keys($updateData)
                ]);
            }
        } else {
            // Create new entry if no duplicate found
            $seedEntry = SeedEntry::create([
                'supplier_id' => $supplier->id,
                'cultivar_name' => $cultivarName,
                'common_name' => $commonName,
                'supplier_product_title' => $productData['title'] ?? 'Unknown Product',
                'supplier_product_url' => $productData['url'] ?? '',
                'image_url' => $productData['image_url'] ?? null,
                'description' => $productData['description'] ?? null,
                'tags' => $productData['tags'] ?? [],
            ]);
            
            Log::info('Created new seed entry', [
                'entry_id' => $seedEntry->id,
                'common_name' => $commonName,
                'cultivar_name' => $cultivarName,
                'supplier' => $supplier->name
            ]);
        }
        
        Log::debug('Processing seed entry', [
            'cultivar' => $cultivarName,
            'common_name' => $commonName,
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
            $successfulVariations = 0;
            $failedVariations = [];
            
            foreach ($variantsArray as $index => $variantData) {
                try {
                    $this->processVariant($variantData, $seedEntry, $timestamp, $defaultCurrency, $productData['is_in_stock'] ?? true);
                    $successfulVariations++;
                } catch (Exception $e) {
                    $failedVariations[] = [
                        'index' => $index,
                        'variation_data' => $variantData,
                        'error' => $e->getMessage()
                    ];
                    
                    Log::warning('Failed to process variation', [
                        'title' => $seedEntry->supplier_product_title,
                        'variation_index' => $index,
                        'variation_size' => $variantData['size'] ?? $variantData['variant_title'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // If all variations failed, throw an exception with detailed info
            if ($successfulVariations === 0 && !empty($failedVariations)) {
                $errorDetails = collect($failedVariations)->map(function($failed) {
                    $size = $failed['variation_data']['size'] ?? $failed['variation_data']['variant_title'] ?? 'Unknown size';
                    return "Variation '{$size}': {$failed['error']}";
                })->join('; ');
                
                throw new Exception("All variations failed: {$errorDetails}");
            }
            
            // If some variations failed but some succeeded, log warning but continue
            if (!empty($failedVariations)) {
                Log::warning('Some variations failed processing', [
                    'title' => $seedEntry->supplier_product_title,
                    'successful_variations' => $successfulVariations,
                    'failed_variations' => count($failedVariations),
                    'failed_details' => $failedVariations
                ]);
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
            $rawPrice = floatval($variantData['price']);
            // Convert 0 price to null for out-of-stock items (this is a common pattern in scraped data)
            $price = $rawPrice > 0 ? $rawPrice : null;
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
        
        // Extract and convert weight information
        $weightData = $this->extractWeightData($variantData, $sizeDescription);
        
        // Find existing variation with same entry and size description
        $variation = SeedVariation::where('seed_entry_id', $seedEntry->id)
            ->where('size_description', $sizeDescription)
            ->first();
            
        if ($variation) {
            // Update existing variation with better price if available
            $updateData = [];
            $priceChanged = false;
            
            if ($price !== null) {
                // Keep the better (lower) price, or set price if none exists
                if ($variation->current_price === null || $variation->current_price == 0 || $price < $variation->current_price) {
                    $updateData['current_price'] = $price;
                    $priceChanged = true;
                }
            }
            
            // Always update currency and stock status to latest
            $updateData['currency'] = $currency;
            $updateData['is_in_stock'] = $isInStock;
            $updateData['last_checked_at'] = now();
            
            // Update weight data if missing
            if (empty($variation->weight_kg) && !empty($weightData['weight_kg'])) {
                $updateData['weight_kg'] = $weightData['weight_kg'];
                $updateData['original_weight_value'] = $weightData['original_weight_value'];
                $updateData['original_weight_unit'] = $weightData['original_weight_unit'];
            }
            
            // Update SKU if missing
            if (empty($variation->sku) && !empty($variantData['sku'])) {
                $updateData['sku'] = $variantData['sku'];
            }
            
            $stockChanged = $variation->is_in_stock !== $isInStock;
            
            $variation->update($updateData);
            
            Log::debug('Updated existing variation', [
                'variation_id' => $variation->id,
                'price' => $price,
                'current_price' => $variation->current_price,
                'is_in_stock' => $isInStock,
                'price_changed' => $priceChanged,
                'stock_changed' => $stockChanged,
                'kept_better_price' => $priceChanged
            ]);
        } else {
            // Create new variation
            $variation = SeedVariation::create([
                'seed_entry_id' => $seedEntry->id,
                'size_description' => $sizeDescription,
                'sku' => $variantData['sku'] ?? null,
                'weight_kg' => $weightData['weight_kg'],
                'original_weight_value' => $weightData['original_weight_value'],
                'original_weight_unit' => $weightData['original_weight_unit'],
                'current_price' => $price, // Allow null for out-of-stock items
                'currency' => $currency,
                'is_in_stock' => $isInStock,
                'last_checked_at' => now(),
            ]);
            
            $priceChanged = true; // New variation counts as price change
            $stockChanged = true; // New variation counts as stock change
            
            Log::debug('Created new variation', [
                'variation_id' => $variation->id,
                'price' => $price,
                'is_in_stock' => $isInStock
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
        
        // Sync with consumable inventory if enabled
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
        try {
            // Check if a consumable already exists for this variation
            if (!$variation->consumable_id) {
                // Create or find a matching consumable based on seed cultivar
                $cultivarName = $seedEntry->cultivar_name ?: 'Unknown Cultivar';
                $consumable = Consumable::firstOrCreate(
                    [
                        'name' => $cultivarName . ' - ' . $variation->size_description,
                        'type' => 'seed',
                    ],
                    [
                        'description' => "Seed: {$cultivarName} ({$variation->size_description})",
                        'unit_type' => 'kg',
                        'unit_weight_grams' => $variation->weight_kg ? $variation->weight_kg * 1000 : null,
                        'total_quantity' => 0,
                        'consumed_quantity' => 0,
                        'restock_threshold' => $variation->weight_kg ? max(1, $variation->weight_kg * 0.5) : 1,
                        'supplier_id' => $seedEntry->supplier_id,
                        'lot_number' => $variation->sku ?? 'SCRAPED-' . $variation->id,
                        'expiry_date' => now()->addYear(), // Default 1 year expiry for seeds
                    ]
                );
                
                // Link the variation to the consumable
                $variation->update(['consumable_id' => $consumable->id]);
                
                Log::info('Created consumable for seed variation', [
                    'variation_id' => $variation->id,
                    'consumable_id' => $consumable->id,
                    'cultivar' => $cultivarName
                ]);
            }
        } catch (Exception $e) {
            Log::error('Error syncing seed variation with consumable inventory', [
                'variation_id' => $variation->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw the exception to prevent import failure
        }
    }
    
    /**
     * Extract cultivar name from product data using intelligent parsing
     *
     * @param array $productData
     * @return string
     */
    protected function extractCultivarName(array $productData): string
    {
        // Method 1: Check for dedicated cultivar fields
        if (isset($productData['cultivar_name']) && $productData['cultivar_name'] !== 'N/A') {
            return $this->cleanSeedName(trim($productData['cultivar_name']));
        }
        
        if (isset($productData['cultivar']) && !empty($productData['cultivar'])) {
            // For Sprouting.com format: cultivar field is the common name, plant_variety is the actual cultivar
            $commonName = trim($productData['cultivar']);
            if (isset($productData['plant_variety']) && 
                $productData['plant_variety'] !== 'N/A' && 
                !empty($productData['plant_variety'])) {
                // The plant_variety is the actual cultivar name
                return $this->cleanSeedName(trim($productData['plant_variety']));
            }
            // If no specific variety, use the common name as cultivar
            return $this->cleanSeedName($commonName);
        }
        
        if (isset($productData['common_name']) && !empty($productData['common_name'])) {
            return $this->cleanSeedName(trim($productData['common_name']));
        }
        
        // Method 2: Parse from title (DamSeeds format)
        if (isset($productData['title']) && !empty($productData['title'])) {
            $title = trim($productData['title']);
            
            // Remove common prefixes/suffixes
            $cleanTitle = preg_replace('/\s*-\s*(Organic|Non-GMO|Heirloom)\s*$/i', '', $title);
            $cleanTitle = preg_replace('/^(Greencrops,\s*)?(\d+\s*)?/i', '', $cleanTitle);
            
            // If title contains comma, take the part before it as base name
            if (strpos($cleanTitle, ',') !== false) {
                $parts = explode(',', $cleanTitle, 2);
                $baseName = trim($parts[0]);
                $variety = isset($parts[1]) ? trim($parts[1]) : '';
                
                // Clean up variety part
                $variety = preg_replace('/^\d+\s*/', '', $variety); // Remove leading numbers
                
                if (!empty($variety)) {
                    return $this->cleanSeedName($baseName . ' - ' . $variety);
                }
                return $this->cleanSeedName($baseName);
            }
            
            return $this->cleanSeedName($cleanTitle);
        }
        
        return 'Unknown Cultivar';
    }
    
    /**
     * Detect currency based on supplier location and data
     *
     * @param array $jsonData
     * @param string $supplierName
     * @return string
     */
    protected function detectCurrency(array $jsonData, string $supplierName): string
    {
        // Check if currency is explicitly provided
        if (isset($jsonData['currency_code']) && !empty($jsonData['currency_code'])) {
            return strtoupper($jsonData['currency_code']);
        }
        
        // Detect based on supplier domain/location
        $supplierLower = strtolower($supplierName);
        
        if (strpos($supplierLower, '.ca') !== false || strpos($supplierLower, 'canada') !== false) {
            return 'CAD';
        }
        
        if (strpos($supplierLower, '.co.uk') !== false || strpos($supplierLower, '.uk') !== false) {
            return 'GBP';
        }
        
        if (strpos($supplierLower, '.eu') !== false || strpos($supplierLower, 'europe') !== false) {
            return 'EUR';
        }
        
        if (strpos($supplierLower, '.au') !== false || strpos($supplierLower, 'australia') !== false) {
            return 'AUD';
        }
        
        // Default to USD for US sites and unknown
        return 'USD';
    }
    
    /**
     * Extract common name from full cultivar name
     *
     * @param string $cultivarName
     * @return string
     */
    protected function extractCommonName(string $cultivarName): string
    {
        if (empty($cultivarName) || $cultivarName === 'Unknown Cultivar') {
            return 'Unknown';
        }
        
        // Remove common suffixes and prefixes
        $cleaned = trim($cultivarName);
        
        // Remove organic/non-gmo/heirloom suffixes
        $cleaned = preg_replace('/\s*-\s*(Organic|Non-GMO|Heirloom|Certified).*$/i', '', $cleaned);
        
        // If there's a dash, take everything before the first dash as the common name
        if (strpos($cleaned, ' - ') !== false) {
            $parts = explode(' - ', $cleaned, 2);
            return trim($parts[0]);
        }
        
        // If there's a comma, take everything before the first comma
        if (strpos($cleaned, ',') !== false) {
            $parts = explode(',', $cleaned, 2);
            return trim($parts[0]);
        }
        
        // For patterns like "Green Forage Pea", "Brussels Winter Vertissimo", etc.
        // Try to extract the main vegetable name
        $words = explode(' ', $cleaned);
        
        // Simple heuristics for common vegetables
        $commonVegetables = [
            'pea' => 'Pea',
            'peas' => 'Pea',
            'beet' => 'Beet',
            'beets' => 'Beet',
            'basil' => 'Basil',
            'brussels' => 'Brussels Sprouts',
            'broccoli' => 'Broccoli',
            'cabbage' => 'Cabbage',
            'carrot' => 'Carrot',
            'carrots' => 'Carrot',
            'lettuce' => 'Lettuce',
            'spinach' => 'Spinach',
            'arugula' => 'Arugula',
            'kale' => 'Kale',
            'chard' => 'Chard',
            'fennel' => 'Fennel',
            'onion' => 'Onion',
            'onions' => 'Onion',
            'leek' => 'Leek',
            'leeks' => 'Leek',
            'radish' => 'Radish',
            'turnip' => 'Turnip',
            'mustard' => 'Mustard',
            'cilantro' => 'Cilantro',
            'parsley' => 'Parsley',
            'dill' => 'Dill',
            'thyme' => 'Thyme',
            'oregano' => 'Oregano',
        ];
        
        // Check each word against common vegetables
        foreach ($words as $word) {
            $lowerWord = strtolower($word);
            if (isset($commonVegetables[$lowerWord])) {
                return $commonVegetables[$lowerWord];
            }
        }
        
        // If no match found, take the first 1-2 words as likely common name
        if (count($words) >= 2) {
            return trim($words[0] . ' ' . $words[1]);
        }
        
        // Return the whole name if no separators found
        return $cleaned;
    }
    
    /**
     * Extract common name directly from product data
     *
     * @param array $productData
     * @return string
     */
    protected function extractCommonNameFromProductData(array $productData): string
    {
        // Method 1: Check for dedicated common_name field
        if (isset($productData['common_name']) && !empty($productData['common_name']) && $productData['common_name'] !== 'N/A') {
            return $this->cleanSeedName(trim($productData['common_name']));
        }
        
        // Method 2: For Sprouting.com format, the 'cultivar' field is actually the common name
        if (isset($productData['cultivar']) && !empty($productData['cultivar'])) {
            return $this->cleanSeedName(trim($productData['cultivar']));
        }
        
        // Method 3: Parse from title for other formats
        if (isset($productData['title']) && !empty($productData['title'])) {
            $title = trim($productData['title']);
            
            // If title contains comma, take the part before it as base name
            if (strpos($title, ',') !== false) {
                $parts = explode(',', $title, 2);
                $baseName = trim($parts[0]);
                
                // Clean up common prefixes/suffixes
                $baseName = preg_replace('/\s*-\s*(Organic|Non-GMO|Heirloom|Certified).*$/i', '', $baseName);
                $baseName = preg_replace('/^(Greencrops,\s*)?(\d+\s*)?/i', '', $baseName);
                
                return $this->cleanSeedName($baseName);
            }
            
            // For patterns like "Green Forage Pea", "Brussels Winter Vertissimo", etc.
            // Try to extract the main vegetable name using the same logic as before
            return $this->extractCommonName($title);
        }
        
        return 'Unknown';
    }
    
    /**
     * Extract and convert weight data from variant information
     *
     * @param array $variantData
     * @param string $sizeDescription
     * @return array
     */
    protected function extractWeightData(array $variantData, string $sizeDescription): array
    {
        $weightKg = null;
        $originalWeightValue = null;
        $originalWeightUnit = null;
        
        // Check if weight_kg is already provided
        if (isset($variantData['weight_kg']) && is_numeric($variantData['weight_kg'])) {
            $weightKg = (float) $variantData['weight_kg'];
            $originalWeightValue = $variantData['original_weight_value'] ?? $weightKg;
            $originalWeightUnit = $variantData['original_weight_unit'] ?? 'kg';
        } else {
            // Try to extract weight from size description
            $weightInfo = $this->parseWeightFromDescription($sizeDescription);
            
            if ($weightInfo) {
                $originalWeightValue = $weightInfo['value'];
                $originalWeightUnit = $weightInfo['unit'];
                $weightKg = $this->convertToKg($weightInfo['value'], $weightInfo['unit']);
            } else {
                // Check original weight fields
                if (isset($variantData['original_weight_value']) && isset($variantData['original_weight_unit'])) {
                    $originalWeightValue = (float) $variantData['original_weight_value'];
                    $originalWeightUnit = $variantData['original_weight_unit'];
                    $weightKg = $this->convertToKg($originalWeightValue, $originalWeightUnit);
                }
            }
        }
        
        return [
            'weight_kg' => $weightKg,
            'original_weight_value' => $originalWeightValue,
            'original_weight_unit' => $originalWeightUnit,
        ];
    }
    
    /**
     * Parse weight information from size description
     *
     * @param string $description
     * @return array|null
     */
    protected function parseWeightFromDescription(string $description): ?array
    {
        // Common weight patterns
        $patterns = [
            '/(\d+(?:\.\d+)?)\s*(kg|kilogram|kilograms)/i' => 'kg',
            '/(\d+(?:\.\d+)?)\s*(g|gram|grams)/i' => 'g',
            '/(\d+(?:\.\d+)?)\s*(lb|lbs|pound|pounds)/i' => 'lbs',
            '/(\d+(?:\.\d+)?)\s*(oz|ounce|ounces)/i' => 'oz',
        ];
        
        foreach ($patterns as $pattern => $unit) {
            if (preg_match($pattern, $description, $matches)) {
                return [
                    'value' => (float) $matches[1],
                    'unit' => $unit,
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Convert weight to kilograms
     *
     * @param float $value
     * @param string $unit
     * @return float
     */
    protected function convertToKg(float $value, string $unit): float
    {
        $unit = strtolower($unit);
        
        switch ($unit) {
            case 'kg':
            case 'kilogram':
            case 'kilograms':
                return $value;
            case 'g':
            case 'gram':
            case 'grams':
                return $value / 1000;
            case 'lb':
            case 'lbs':
            case 'pound':
            case 'pounds':
                return $value * 0.453592;
            case 'oz':
            case 'ounce':
            case 'ounces':
                return $value * 0.0283495;
            default:
                Log::warning("Unknown weight unit: {$unit}, treating as kg");
                return $value;
        }
    }
    
    /**
     * Clean and normalize seed names to fix common data quality issues
     *
     * @param string $name
     * @return string
     */
    protected function cleanSeedName(string $name): string
    {
        if (empty($name)) {
            return $name;
        }
        
        $cleaned = trim($name);
        
        // Handle complex patterns first (more specific to less specific)
        // Handle patterns like "() - Microgreens" first (most specific)
        $cleaned = preg_replace('/^\s*\(\s*\)\s*-\s*Microgreens\s*$/', 'Microgreens', $cleaned); // "() - Microgreens" -> "Microgreens"
        // Then handle patterns like "Basic Salad Mix () - Microgreens"
        $cleaned = preg_replace('/\s*\(\s*\)\s*-\s*Microgreens\s*$/', '', $cleaned);
        $cleaned = preg_replace('/^\s*\(\s*\)\s*-\s*/', '', $cleaned); // Handle leading "() - " patterns
        
        // Clean up common suffixes that often get malformed
        $cleaned = preg_replace('/\s*-\s*Microgreens\s*$/', '', $cleaned); // Remove "- Microgreens" suffix
        $cleaned = preg_replace('/\s*Microgreens\s*$/', '', $cleaned); // Remove standalone "Microgreens" suffix
        
        // Remove empty brackets and clean up common patterns
        $cleaned = preg_replace('/\s*\(\s*\)\s*/', '', $cleaned); // Remove "()" empty brackets
        $cleaned = preg_replace('/\s*\(\s+\)\s*/', '', $cleaned); // Remove "( )" brackets with just spaces
        
        // Clean up parentheses that only contain organic/certification info that got lost
        $cleaned = preg_replace('/\s*\(\s*(Organic|Non-GMO|Heirloom|Certified)\s*\)/', ' (\1)', $cleaned);
        
        // Remove leading/trailing dashes and extra spaces
        $cleaned = preg_replace('/^\s*-\s*|\s*-\s*$/', '', $cleaned);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned); // Collapse multiple spaces
        
        // Trim again after all cleaning
        $cleaned = trim($cleaned);
        
        // If we accidentally cleaned everything away, return original
        if (empty($cleaned)) {
            return trim($name);
        }
        
        return $cleaned;
    }
} 
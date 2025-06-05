<?php

namespace App\Services;

use App\Models\Consumable;
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
            
            // Get currency code from top level if available, or detect from supplier
            $currencyCode = $this->detectCurrency($jsonData, $supplierName);
            
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
        $cultivarName = $this->extractCultivarName($productData);
        $commonName = $this->extractCommonName($cultivarName);
        
        // Find or create the seed entry with cultivar and common name populated directly
        $seedEntry = SeedEntry::firstOrCreate(
            [
                'supplier_id' => $supplier->id,
                'supplier_product_url' => $productData['url'] ?? '',
            ],
            [
                'cultivar_name' => $cultivarName,
                'common_name' => $commonName,
                'supplier_product_title' => $productData['title'] ?? 'Unknown Product',
                'image_url' => $productData['image_url'] ?? null,
                'description' => $productData['description'] ?? null,
                'tags' => $productData['tags'] ?? [],
            ]
        );
        
        // Update existing entries if cultivar or common name fields are empty
        if (empty($seedEntry->cultivar_name) || empty($seedEntry->common_name)) {
            $seedEntry->update([
                'cultivar_name' => $cultivarName,
                'common_name' => $commonName,
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
            return trim($productData['cultivar_name']);
        }
        
        if (isset($productData['cultivar']) && !empty($productData['cultivar'])) {
            // Combine with plant_variety if available (Sprouting.com format)
            $cultivar = trim($productData['cultivar']);
            if (isset($productData['plant_variety']) && 
                $productData['plant_variety'] !== 'N/A' && 
                !empty($productData['plant_variety'])) {
                return $cultivar . ' - ' . trim($productData['plant_variety']);
            }
            return $cultivar;
        }
        
        if (isset($productData['common_name']) && !empty($productData['common_name'])) {
            return trim($productData['common_name']);
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
                    return $baseName . ' - ' . $variety;
                }
                return $baseName;
            }
            
            return $cleanTitle;
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
} 
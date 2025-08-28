<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicateDefaultVariations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'variations:fix-duplicates {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix duplicate Default price variations and assign proper names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info($dryRun ? 'Running in DRY RUN mode - no changes will be made' : 'Fixing duplicate default variations...');
        
        DB::beginTransaction();
        
        try {
            // Get all products with their variations
            $products = Product::with(['priceVariations' => function($query) {
                $query->orderBy('is_default', 'desc')->orderBy('id', 'asc');
            }])->get();
            
            $fixedCount = 0;
            
            foreach ($products as $product) {
                $variations = $product->priceVariations;
                
                // Skip if no variations or only one variation
                if ($variations->count() <= 1) {
                    continue;
                }
                
                // Check if all variations are named "Default"
                $allDefault = $variations->every(fn($v) => $v->name === 'Default');
                
                if (!$allDefault) {
                    continue;
                }
                
                $this->info("\nProduct: {$product->name} (ID: {$product->id})");
                $this->info("Found {$variations->count()} variations all named 'Default'");
                
                // First variation stays as Default
                $defaultVar = $variations->first();
                $this->line("  - Keeping variation ID {$defaultVar->id} as 'Default'" . ($defaultVar->is_default ? ' (already default)' : ' (will set as default)'));
                
                // Assign proper names to others based on product prices
                $index = 1;
                foreach ($variations->skip(1) as $variation) {
                    $newName = $this->determineVariationName($product, $variation, $index);
                    
                    // Check if inventory exists for this variation
                    $hasInventory = ProductInventory::where('price_variation_id', $variation->id)->exists();
                    
                    if ($hasInventory) {
                        $this->line("  - Will update variation ID {$variation->id} to '{$newName}'");
                    } else {
                        $this->line("  - Will delete unused variation ID {$variation->id}");
                    }
                    
                    $index++;
                }
                
                if (!$dryRun) {
                    // Apply the changes
                    $defaultVar->update(['is_default' => true]);
                    
                    $index = 1;
                    foreach ($variations->skip(1) as $variation) {
                        $newName = $this->determineVariationName($product, $variation, $index);
                        $hasInventory = ProductInventory::where('price_variation_id', $variation->id)->exists();
                        
                        if ($hasInventory) {
                            $variation->update([
                                'name' => $newName,
                                'is_default' => false
                            ]);
                        } else {
                            $variation->delete();
                        }
                        
                        $index++;
                    }
                }
                
                $fixedCount++;
            }
            
            // Also fix global variations that might be duplicated
            $this->fixGlobalVariations($dryRun);
            
            if ($dryRun) {
                DB::rollBack();
                $this->info("\nDRY RUN COMPLETE - Would have fixed {$fixedCount} products");
                $this->warn("Run without --dry-run to apply these fixes.");
            } else {
                DB::commit();
                $this->info("\nSUCCESS - Fixed {$fixedCount} products");
            }
            
        } catch (Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function determineVariationName(Product $product, PriceVariation $variation, int $index): string
    {
        // Try to determine based on price comparison
        if ($product->wholesale_price && abs($variation->price - $product->wholesale_price) < 0.01) {
            return 'Wholesale';
        }
        
        if ($product->bulk_price && abs($variation->price - $product->bulk_price) < 0.01) {
            return 'Bulk';
        }
        
        if ($product->special_price && abs($variation->price - $product->special_price) < 0.01) {
            return 'Special';
        }
        
        // Otherwise, use a generic name based on index
        return match($index) {
            1 => 'Wholesale',
            2 => 'Bulk',
            3 => 'Special',
            default => "Variation {$index}"
        };
    }
    
    private function fixGlobalVariations(bool $dryRun): void
    {
        $globalVariations = PriceVariation::whereNull('product_id')
            ->where('is_global', true)
            ->get();
            
        if ($globalVariations->isEmpty()) {
            return;
        }
        
        $this->info("\nChecking global variations...");
        
        // Group by name to find duplicates
        $grouped = $globalVariations->groupBy('name');
        
        foreach ($grouped as $name => $variations) {
            if ($variations->count() <= 1) {
                continue;
            }
            
            $this->info("Found {$variations->count()} global variations named '{$name}'");
            
            if (!$dryRun) {
                // Keep the first one, delete the rest
                $keep = $variations->first();
                foreach ($variations->skip(1) as $variation) {
                    // Check if it's being used
                    $usageCount = ProductInventory::where('price_variation_id', $variation->id)->count();
                    
                    if ($usageCount > 0) {
                        $this->warn("  - Cannot delete variation ID {$variation->id} - used in {$usageCount} inventory entries");
                    } else {
                        $variation->delete();
                        $this->line("  - Deleted duplicate global variation ID {$variation->id}");
                    }
                }
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\PriceVariation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixProductInventoryMismatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:fix-mismatches {--dry-run : Show what would be fixed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix product inventory entries that are mismatched with price variations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info($dryRun ? 'Running in DRY RUN mode - no changes will be made' : 'Fixing product inventory mismatches...');
        
        DB::beginTransaction();
        
        try {
            // 1. Find orphaned inventory entries (no price variation)
            $orphanedCount = $this->fixOrphanedInventories($dryRun);
            
            // 2. Find duplicate inventory entries
            $duplicateCount = $this->fixDuplicateInventories($dryRun);
            
            // 3. Find inventory entries where price variation doesn't match product
            $mismatchedCount = $this->fixMismatchedInventories($dryRun);
            
            // 4. Ensure all active price variations have inventory entries
            $createdCount = $this->ensureInventoryForAllVariations($dryRun);
            
            if ($dryRun) {
                DB::rollBack();
                $this->info("\nDRY RUN SUMMARY:");
            } else {
                DB::commit();
                $this->info("\nFIXES APPLIED:");
            }
            
            $this->info("- Orphaned inventories removed: {$orphanedCount}");
            $this->info("- Duplicate inventories merged: {$duplicateCount}");
            $this->info("- Mismatched inventories fixed: {$mismatchedCount}");
            $this->info("- Missing inventories created: {$createdCount}");
            
            if ($dryRun) {
                $this->warn("\nRun without --dry-run to apply these fixes.");
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function fixOrphanedInventories(bool $dryRun): int
    {
        $orphaned = ProductInventory::whereNull('price_variation_id')->get();
        
        if ($orphaned->count() > 0) {
            $this->info("\nFound {$orphaned->count()} orphaned inventory entries:");
            
            foreach ($orphaned as $inventory) {
                $this->line("  - Product: {$inventory->product->name}, Quantity: {$inventory->quantity}");
                
                if (!$dryRun) {
                    // Try to match to default price variation
                    $defaultVariation = $inventory->product->priceVariations()
                        ->where('is_default', true)
                        ->first();
                    
                    if ($defaultVariation) {
                        $inventory->price_variation_id = $defaultVariation->id;
                        $inventory->save();
                        $this->line("    → Linked to default variation: {$defaultVariation->name}");
                    } else {
                        $inventory->delete();
                        $this->line("    → Deleted (no default variation found)");
                    }
                }
            }
        }
        
        return $orphaned->count();
    }
    
    private function fixDuplicateInventories(bool $dryRun): int
    {
        $duplicates = DB::select('
            SELECT product_id, price_variation_id, COUNT(*) as count
            FROM product_inventories
            WHERE price_variation_id IS NOT NULL
            GROUP BY product_id, price_variation_id
            HAVING count > 1
        ');
        
        $totalFixed = 0;
        
        if (count($duplicates) > 0) {
            $this->info("\nFound duplicate inventory entries:");
            
            foreach ($duplicates as $dup) {
                $inventories = ProductInventory::where('product_id', $dup->product_id)
                    ->where('price_variation_id', $dup->price_variation_id)
                    ->orderBy('quantity', 'desc')
                    ->get();
                
                $product = Product::find($dup->product_id);
                $variation = PriceVariation::find($dup->price_variation_id);
                
                $this->line("  - Product: {$product->name}, Variation: {$variation->name}, Duplicates: {$dup->count}");
                
                if (!$dryRun) {
                    // Keep the first one (highest quantity), merge quantities
                    $keepInventory = $inventories->first();
                    $totalQuantity = $inventories->sum('quantity');
                    $totalReserved = $inventories->sum('reserved_quantity');
                    
                    $keepInventory->quantity = $totalQuantity;
                    $keepInventory->reserved_quantity = $totalReserved;
                    $keepInventory->save();
                    
                    // Delete the rest
                    foreach ($inventories->skip(1) as $inventory) {
                        $inventory->delete();
                    }
                    
                    $this->line("    → Merged into single entry with quantity: {$totalQuantity}");
                }
                
                $totalFixed += $dup->count - 1;
            }
        }
        
        return $totalFixed;
    }
    
    private function fixMismatchedInventories(bool $dryRun): int
    {
        $mismatched = ProductInventory::whereNotNull('price_variation_id')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM price_variations pv 
                WHERE pv.id = product_inventories.price_variation_id 
                AND pv.product_id = product_inventories.product_id
            )')
            ->get();
        
        if ($mismatched->count() > 0) {
            $this->info("\nFound {$mismatched->count()} mismatched inventory entries:");
            
            foreach ($mismatched as $inventory) {
                $variation = PriceVariation::find($inventory->price_variation_id);
                $this->line("  - Product: {$inventory->product->name}, Wrong Variation: " . ($variation ? $variation->name : 'deleted'));
                
                if (!$dryRun) {
                    // Try to find correct variation with same name
                    $correctVariation = $inventory->product->priceVariations()
                        ->where('name', $variation ? $variation->name : 'Default')
                        ->first();
                    
                    if (!$correctVariation) {
                        $correctVariation = $inventory->product->priceVariations()
                            ->where('is_default', true)
                            ->first();
                    }
                    
                    if ($correctVariation) {
                        $inventory->price_variation_id = $correctVariation->id;
                        $inventory->save();
                        $this->line("    → Fixed: linked to {$correctVariation->name}");
                    } else {
                        $inventory->delete();
                        $this->line("    → Deleted (no matching variation found)");
                    }
                }
            }
        }
        
        return $mismatched->count();
    }
    
    private function ensureInventoryForAllVariations(bool $dryRun): int
    {
        $created = 0;
        
        $products = Product::with(['priceVariations', 'inventories'])->get();
        
        foreach ($products as $product) {
            $activeVariations = $product->priceVariations()->where('is_active', true)->get();
            
            foreach ($activeVariations as $variation) {
                $hasInventory = $product->inventories()
                    ->where('price_variation_id', $variation->id)
                    ->exists();
                
                if (!$hasInventory) {
                    if ($created === 0) {
                        $this->info("\nCreating missing inventory entries:");
                    }
                    
                    $this->line("  - Product: {$product->name}, Variation: {$variation->name}");
                    
                    if (!$dryRun) {
                        ProductInventory::create([
                            'product_id' => $product->id,
                            'price_variation_id' => $variation->id,
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            'cost_per_unit' => 0,
                            'status' => 'active',
                            'notes' => 'Created by fix command',
                        ]);
                    }
                    
                    $created++;
                }
            }
        }
        
        return $created;
    }
}

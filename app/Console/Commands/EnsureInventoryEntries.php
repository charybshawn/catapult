<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class EnsureInventoryEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:ensure-entries {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure inventory entries exist for all active price variations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🔄 Checking products for missing inventory entries...');

        $products = Product::with(['priceVariations', 'inventories'])
            ->where('active', true)
            ->get();

        $totalCreated = 0;
        $productsProcessed = 0;

        foreach ($products as $product) {
            $this->line("📦 Processing: {$product->name}");
            
            $activeVariations = $product->priceVariations()
                ->where('is_active', true)
                ->get();

            if ($activeVariations->isEmpty()) {
                $this->warn("  ⚠️  No active price variations found");
                continue;
            }

            $createdForProduct = 0;

            foreach ($activeVariations as $variation) {
                $existingInventory = $product->inventories()
                    ->where('price_variation_id', $variation->id)
                    ->first();

                if (!$existingInventory) {
                    if (!$isDryRun) {
                        $product->inventories()->create([
                            'price_variation_id' => $variation->id,
                            'batch_number' => $product->getNextBatchNumber() . '-' . strtoupper(substr($variation->name, 0, 3)),
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            'cost_per_unit' => 0,
                            'production_date' => now(),
                            'expiration_date' => null,
                            'location' => null,
                            'status' => 'active',
                            'notes' => "Auto-created for {$variation->name} variation",
                        ]);
                    }
                    
                    $this->line("  ✅ " . ($isDryRun ? 'Would create' : 'Created') . " inventory for: {$variation->name}");
                    $createdForProduct++;
                } else {
                    $this->line("  ✓ Inventory already exists for: {$variation->name}");
                }
            }

            if ($createdForProduct > 0) {
                $productsProcessed++;
                $totalCreated += $createdForProduct;
            }
        }

        $this->newLine();
        if ($isDryRun) {
            $this->info("🎯 DRY RUN COMPLETE:");
            $this->info("   - Would create {$totalCreated} inventory entries");
            $this->info("   - Across {$productsProcessed} products");
            $this->info("   - Run without --dry-run to actually create the entries");
        } else {
            $this->info("✅ TASK COMPLETE:");
            $this->info("   - Created {$totalCreated} inventory entries");
            $this->info("   - Processed {$productsProcessed} products");
        }

        return Command::SUCCESS;
    }
}

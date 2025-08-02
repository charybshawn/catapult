<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Consumable;
use App\Models\MasterSeedCatalog;
use App\Models\Supplier;

class FixConsumableRelationships extends Command
{
    protected $signature = 'consumables:fix-relationships';
    protected $description = 'Fix missing supplier and master seed catalog relationships for consumables';

    public function handle()
    {
        $this->info('Starting to fix consumable relationships...');

        // Get all seed consumables with missing relationships
        $seedConsumables = Consumable::with('consumableType')
            ->whereHas('consumableType', function($q) { 
                $q->where('code', 'seed'); 
            })
            ->where(function($query) {
                $query->whereNull('supplier_id')
                      ->orWhereNull('master_seed_catalog_id');
            })
            ->get();

        $this->info("Found {$seedConsumables->count()} seed consumables with missing relationships");

        // Default supplier for seeds
        $defaultSeedSupplier = Supplier::where('name', 'Mumm\'s Sprouting Seeds')->first();
        if (!$defaultSeedSupplier) {
            $defaultSeedSupplier = Supplier::first(); // Fallback to first supplier
        }

        $this->info("Using default supplier: {$defaultSeedSupplier->name} (ID: {$defaultSeedSupplier->id})");

        $updated = 0;
        $skipped = 0;

        foreach ($seedConsumables as $consumable) {
            $this->line("\nProcessing: {$consumable->name} (ID: {$consumable->id})");
            
            // Parse the name to extract common name and cultivar
            // Format is typically: "Common Name (Cultivar)"
            if (preg_match('/^(.+?)\s*\((.+?)\)$/', $consumable->name, $matches)) {
                $commonName = trim($matches[1]);
                $cultivar = trim($matches[2]);
                
                $this->line("  Parsed - Common: '$commonName', Cultivar: '$cultivar'");
                
                // Find matching master seed catalog
                $masterCatalog = MasterSeedCatalog::whereRaw('LOWER(common_name) = ?', [strtolower($commonName)])->first();
                
                if ($masterCatalog) {
                    $this->line("  Found master catalog: {$masterCatalog->common_name} (ID: {$masterCatalog->id})");
                    
                    // Check if the cultivar exists in the catalog
                    $masterCultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $masterCatalog->id)
                        ->whereRaw('LOWER(cultivar_name) = ?', [strtolower(trim($cultivar))])
                        ->first();
                    
                    if ($masterCultivar) {
                        $this->line("  Found matching cultivar: {$masterCultivar->cultivar_name}");
                        
                        // Update the consumable with catalog and cultivar IDs
                        $consumable->update([
                            'supplier_id' => $defaultSeedSupplier->id,
                            'master_seed_catalog_id' => $masterCatalog->id,
                            'master_cultivar_id' => $masterCultivar->id,
                            'cultivar' => $masterCultivar->cultivar_name
                        ]);
                        
                        $this->info("  ✓ Updated successfully");
                        $updated++;
                    } else {
                        $availableCultivars = \App\Models\MasterCultivar::where('master_seed_catalog_id', $masterCatalog->id)
                            ->where('is_active', true)
                            ->pluck('cultivar_name')
                            ->toArray();
                        
                        $this->warn("  ✗ Cultivar '$cultivar' not found in catalog. Available: " . implode(', ', $availableCultivars));
                        
                        // Still update with supplier and catalog, use the first available cultivar
                        $firstCultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $masterCatalog->id)
                            ->where('is_active', true)
                            ->first();
                        
                        if ($firstCultivar) {
                            $consumable->update([
                                'supplier_id' => $defaultSeedSupplier->id,
                                'master_seed_catalog_id' => $masterCatalog->id,
                                'master_cultivar_id' => $firstCultivar->id,
                                'cultivar' => $firstCultivar->cultivar_name
                            ]);
                            $this->warn("  ⚠ Updated with default cultivar: {$firstCultivar->cultivar_name}");
                        } else {
                            $consumable->update([
                                'supplier_id' => $defaultSeedSupplier->id,
                                'master_seed_catalog_id' => $masterCatalog->id,
                            ]);
                            $this->warn("  ⚠ Updated without cultivar (none available)");
                        }
                        $updated++;
                    }
                } else {
                    $this->error("  ✗ No master catalog found for '$commonName'");
                    
                    // Still update with supplier
                    $consumable->update([
                        'supplier_id' => $defaultSeedSupplier->id,
                        'cultivar' => $cultivar
                    ]);
                    
                    $this->warn("  ⚠ Updated with supplier only");
                    $updated++;
                }
            } else {
                $this->error("  ✗ Could not parse name format: {$consumable->name}");
                
                // Still update with supplier
                $consumable->update([
                    'supplier_id' => $defaultSeedSupplier->id
                ]);
                
                $this->warn("  ⚠ Updated with supplier only");
                $updated++;
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Updated: $updated");
        $this->info("Skipped: $skipped");
        $this->info("Done!");
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Consumable;
use App\Models\MasterSeedCatalog;

class LinkConsumablesToMasterCatalog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consumables:link-to-catalog {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link seed consumables to master catalog entries by parsing their names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN - No changes will be made');
        }
        
        $seedConsumables = Consumable::where('type', 'seed')
            ->whereNull('master_seed_catalog_id')
            ->get();
            
        $this->info("Found {$seedConsumables->count()} seed consumables without master catalog links");
        
        $linked = 0;
        $created = 0;
        $skipped = 0;
        
        foreach ($seedConsumables as $consumable) {
            $this->line("Processing: {$consumable->name}");
            
            // Parse the name format: "Common Name (Cultivar)"
            if (preg_match('/^(.+?)\s*\((.+?)\)$/', $consumable->name, $matches)) {
                $commonName = trim($matches[1]);
                $cultivarName = trim($matches[2]);
                
                $this->info("  Parsed: Common='{$commonName}', Cultivar='{$cultivarName}'");
                
                // Try to find existing cultivar with this exact name
                $masterCultivar = \App\Models\MasterCultivar::whereHas('masterSeedCatalog', function ($query) use ($commonName) {
                        $query->where('common_name', $commonName);
                    })
                    ->whereRaw('LOWER(cultivar_name) = ?', [strtolower($cultivarName)])
                    ->with('masterSeedCatalog')
                    ->first();
                    
                if ($masterCultivar) {
                    $this->info("  Found existing cultivar: {$masterCultivar->cultivar_name} in catalog: {$masterCultivar->masterSeedCatalog->common_name}");
                    if (!$dryRun) {
                        $consumable->update([
                            'master_seed_catalog_id' => $masterCultivar->masterSeedCatalog->id,
                            'master_cultivar_id' => $masterCultivar->id
                        ]);
                    }
                    $linked++;
                } else {
                    // Check if there's a catalog with same common name but no matching cultivar
                    $existingCatalog = MasterSeedCatalog::where('common_name', $commonName)->first();
                    
                    if ($existingCatalog) {
                        // Create new cultivar for existing catalog
                        $this->warn("  Adding '{$cultivarName}' cultivar to existing '{$commonName}' catalog");
                        if (!$dryRun) {
                            $newCultivar = \App\Models\MasterCultivar::create([
                                'master_seed_catalog_id' => $existingCatalog->id,
                                'cultivar_name' => $cultivarName,
                                'is_active' => true,
                            ]);
                            
                            $consumable->update([
                                'master_seed_catalog_id' => $existingCatalog->id,
                                'master_cultivar_id' => $newCultivar->id
                            ]);
                        }
                        $linked++;
                    } else {
                        // Create new catalog and cultivar
                        $this->warn("  No existing catalog found, creating new entry with cultivar");
                        
                        if (!$dryRun) {
                            // Create catalog first
                            $masterCatalog = MasterSeedCatalog::create([
                                'common_name' => $commonName,
                                'category' => $this->getCategoryForSeed($commonName),
                                'description' => "Auto-created from consumable: {$consumable->name}",
                                'is_active' => true,
                            ]);
                            
                            // Create cultivar
                            $newCultivar = \App\Models\MasterCultivar::create([
                                'master_seed_catalog_id' => $masterCatalog->id,
                                'cultivar_name' => $cultivarName,
                                'is_active' => true,
                            ]);
                            
                            // Update catalog to point to this cultivar as primary
                            $masterCatalog->update(['cultivar_id' => $newCultivar->id]);
                            
                            // Link consumable
                            $consumable->update([
                                'master_seed_catalog_id' => $masterCatalog->id,
                                'master_cultivar_id' => $newCultivar->id
                            ]);
                            
                            $this->info("  Created catalog entry: {$masterCatalog->id} with cultivar: {$newCultivar->id}");
                        }
                        $created++;
                    }
                }
            } else {
                $this->error("  Could not parse name format: {$consumable->name}");
                $skipped++;
            }
        }
        
        $this->newLine();
        $this->info("Summary:");
        $this->info("  Linked to existing catalog: {$linked}");
        $this->info("  Created new catalog entries: {$created}");
        $this->info("  Skipped (unparseable): {$skipped}");
        
        if ($dryRun) {
            $this->warn("This was a dry run. Use without --dry-run to apply changes.");
        }
    }
    
    /**
     * Get appropriate category for a seed based on common name
     */
    private function getCategoryForSeed(string $commonName): string
    {
        $categories = [
            'Herbs' => ['Basil', 'Dill', 'Coriander', 'Fenugreek'],
            'Brassicas' => ['Arugula', 'Kale', 'Kohlrabi', 'Broccoli', 'Mustard', 'Radish', 'Cress'],
            'Legumes' => ['Peas', 'Lentils'],
            'Greens' => ['Beet', 'Swiss Chard', 'Amaranth'],
            'Grains' => ['Sunflower'],
            'Other' => ['Borage'],
        ];
        
        foreach ($categories as $category => $seeds) {
            foreach ($seeds as $seed) {
                if (stripos($commonName, $seed) !== false) {
                    return $category;
                }
            }
        }
        
        return 'Other';
    }
}

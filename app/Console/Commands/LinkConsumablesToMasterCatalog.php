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
                
                // Try to find existing master catalog entry that contains this cultivar
                $masterCatalog = MasterSeedCatalog::where('common_name', $commonName)
                    ->where('cultivars', 'LIKE', '%"' . $cultivarName . '"%')
                    ->first();
                    
                if ($masterCatalog) {
                    $this->info("  Found existing catalog entry: {$masterCatalog->id}");
                    if (!$dryRun) {
                        $consumable->update(['master_seed_catalog_id' => $masterCatalog->id]);
                    }
                    $linked++;
                } else {
                    // Check if there's an entry with same common name but different cultivars
                    $existingWithSameName = MasterSeedCatalog::where('common_name', $commonName)->first();
                    
                    if ($existingWithSameName) {
                        // Add this cultivar to the existing entry if it's not already there
                        $existingCultivars = $existingWithSameName->cultivars ?: [];
                        if (!in_array($cultivarName, $existingCultivars)) {
                            $this->warn("  Adding '{$cultivarName}' to existing '{$commonName}' catalog entry");
                            if (!$dryRun) {
                                $existingCultivars[] = $cultivarName;
                                // Update without triggering model events to avoid syncCultivars issues
                                $existingWithSameName->updateQuietly(['cultivars' => $existingCultivars]);
                            }
                        }
                        
                        $this->info("  Linking to existing catalog entry: {$existingWithSameName->id}");
                        if (!$dryRun) {
                            $consumable->update(['master_seed_catalog_id' => $existingWithSameName->id]);
                        }
                        $linked++;
                    } else {
                        // Create new master catalog entry
                        $this->warn("  No existing catalog found, creating new entry");
                        
                        if (!$dryRun) {
                            $masterCatalog = MasterSeedCatalog::create([
                                'common_name' => $commonName,
                                'cultivars' => [$cultivarName],
                                'category' => $this->getCategoryForSeed($commonName),
                                'description' => "Auto-created from consumable: {$consumable->name}",
                                'is_active' => true,
                            ]);
                            
                            $consumable->update(['master_seed_catalog_id' => $masterCatalog->id]);
                            $this->info("  Created catalog entry: {$masterCatalog->id}");
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

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;

class MigrateCultivarsToRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cultivars:migrate {--dry-run : Show what would be created without actually creating records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '[OBSOLETE] Migrate JSON cultivars from MasterSeedCatalog to MasterCultivar records - No longer needed with new schema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->error('This command is obsolete. The schema has been updated to use proper foreign key relationships.');
        $this->info('Cultivars are now stored in the master_cultivars table with proper relationships.');
        return Command::FAILURE;
        
        if ($isDryRun) {
            $this->info('DRY RUN - No records will be created');
            $this->newLine();
        }

        $catalogs = MasterSeedCatalog::whereNotNull('cultivars')->get();
        
        if ($catalogs->isEmpty()) {
            $this->info('No MasterSeedCatalog records with cultivars found.');
            return 0;
        }

        $this->info("Found {$catalogs->count()} catalogs with cultivars to migrate:");
        $this->newLine();

        $totalCreated = 0;

        foreach ($catalogs as $catalog) {
            $cultivars = is_array($catalog->cultivars) ? $catalog->cultivars : [];
            
            if (empty($cultivars)) {
                continue;
            }

            $this->info("Catalog: {$catalog->common_name}");
            
            foreach ($cultivars as $cultivarName) {
                if (empty(trim($cultivarName))) {
                    continue;
                }

                // Check if cultivar already exists
                $existing = MasterCultivar::where('master_seed_catalog_id', $catalog->id)
                    ->where('cultivar_name', trim($cultivarName))
                    ->first();

                if ($existing) {
                    $this->line("  - {$cultivarName} (already exists)");
                    continue;
                }

                if ($isDryRun) {
                    $this->line("  - {$cultivarName} (would create)");
                    $totalCreated++;
                } else {
                    try {
                        MasterCultivar::create([
                            'master_seed_catalog_id' => $catalog->id,
                            'cultivar_name' => trim($cultivarName),
                            'is_active' => true,
                        ]);
                        $this->line("  ✓ {$cultivarName} (created)");
                        $totalCreated++;
                    } catch (\Exception $e) {
                        $this->error("  ✗ {$cultivarName} (failed: {$e->getMessage()})");
                    }
                }
            }
            
            $this->newLine();
        }

        if ($isDryRun) {
            $this->info("Would create {$totalCreated} MasterCultivar records.");
            $this->info('Run without --dry-run to actually create the records.');
        } else {
            $this->info("Successfully created {$totalCreated} MasterCultivar records.");
            
            if ($totalCreated > 0) {
                $this->newLine();
                $this->info('You can now use the new cultivar-based form fields!');
            }
        }

        return 0;
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Consumable;
use App\Models\ConsumableType;

class VerifyConsumableNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consumables:verify-names {--fix : Fix any inconsistencies found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify and optionally fix consumable name inconsistencies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verifying consumable names...');
        
        $seedTypeId = ConsumableType::where('code', 'seed')->value('id');
        if (!$seedTypeId) {
            $this->error('Seed consumable type not found!');
            return 1;
        }
        
        // Get all seed consumables with relationships
        $seedConsumables = Consumable::with(['masterSeedCatalog', 'masterCultivar', 'consumableType'])
            ->where('consumable_type_id', $seedTypeId)
            ->get();
            
        $issues = [];
        
        foreach ($seedConsumables as $consumable) {
            $errors = $consumable->getSeedRelationshipErrors();
            if (!empty($errors)) {
                $issues[] = [
                    'id' => $consumable->id,
                    'current_name' => $consumable->getAttributes()['name'] ?? 'NULL',
                    'computed_name' => $consumable->name, // Uses accessor
                    'errors' => $errors
                ];
            }
        }
        
        if (empty($issues)) {
            $this->info('✅ All seed consumables have valid relationships and computed names!');
            return 0;
        }
        
        $this->warn('Found ' . count($issues) . ' seed consumables with issues:');
        
        foreach ($issues as $issue) {
            $this->line('');
            $this->line("ID: {$issue['id']}");
            $this->line("Stored name: {$issue['current_name']}");
            $this->line("Computed name: {$issue['computed_name']}");
            foreach ($issue['errors'] as $error) {
                $this->error("  ❌ {$error}");
            }
        }
        
        if ($this->option('fix')) {
            $this->line('');
            $this->warn('Fixing would require manual intervention - incomplete relationships need to be resolved first.');
            $this->info('Please ensure all seed consumables have proper master_seed_catalog_id and master_cultivar_id relationships.');
        }
        
        return count($issues) > 0 ? 1 : 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Consumable;

class VerifyConsumableRelationships extends Command
{
    protected $signature = 'consumables:verify-relationships';
    protected $description = 'Verify cultivar relationships in seed consumables';

    public function handle()
    {
        $this->info('Checking for cultivar mismatches in seed consumables...');
        
        $consumables = Consumable::with(['consumableType', 'masterSeedCatalog', 'masterCultivar'])->get();
        $mismatches = [];
        
        foreach ($consumables as $consumable) {
            if ($consumable->consumableType && $consumable->consumableType->code === 'seed' && 
                $consumable->master_seed_catalog_id && $consumable->master_cultivar_id &&
                $consumable->masterCultivar && $consumable->masterSeedCatalog &&
                $consumable->masterCultivar->master_seed_catalog_id !== $consumable->master_seed_catalog_id) {
                $mismatches[] = $consumable;
            }
        }
        
        if (count($mismatches) > 0) {
            $this->error('Found ' . count($mismatches) . ' mismatched records:');
            foreach ($mismatches as $consumable) {
                $this->line("ID {$consumable->id}: Catalog {$consumable->master_seed_catalog_id} ({$consumable->masterSeedCatalog->common_name}) but Cultivar {$consumable->master_cultivar_id} ({$consumable->masterCultivar->cultivar_name}) belongs to catalog {$consumable->masterCultivar->master_seed_catalog_id}");
            }
        } else {
            $this->info('No cultivar mismatches found - all relationships are correct!');
        }
        
        return 0;
    }
}
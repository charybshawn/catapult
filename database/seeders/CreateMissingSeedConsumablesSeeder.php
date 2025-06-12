<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SeedEntry;
use App\Models\Consumable;
use Illuminate\Support\Facades\DB;

class CreateMissingSeedConsumablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder creates consumables only for seed entries that don't already have one.
     */
    public function run(): void
    {
        $this->command->info('Creating consumables for seed entries without existing consumables...');
        
        // Get seed entries that don't have consumables
        $seedEntriesWithoutConsumables = SeedEntry::whereDoesntHave('consumables', function ($query) {
            $query->where('type', 'seed');
        })
        ->where('is_active', true)
        ->with('supplier')
        ->get();
        
        if ($seedEntriesWithoutConsumables->isEmpty()) {
            $this->command->info('All active seed entries already have consumables.');
            return;
        }
        
        $this->command->info("Found {$seedEntriesWithoutConsumables->count()} seed entries without consumables.");
        
        $created = 0;
        
        foreach ($seedEntriesWithoutConsumables as $seedEntry) {
            try {
                // Create the consumable with realistic defaults
                $consumable = Consumable::create([
                    'name' => $seedEntry->common_name . ' (' . $seedEntry->cultivar_name . ')',
                    'type' => 'seed',
                    'seed_entry_id' => $seedEntry->id,
                    'supplier_id' => $seedEntry->supplier_id,
                    'initial_stock' => 1, // For seeds, we use total_quantity
                    'consumed_quantity' => 0,
                    'total_quantity' => 0, // Start with 0, user will add actual inventory
                    'quantity_unit' => 'g', // Default to grams
                    'quantity_per_unit' => 1,
                    'unit' => 'unit',
                    'cost_per_unit' => null, // Let user set actual cost
                    'restock_threshold' => 250, // Default 250g threshold
                    'restock_quantity' => 1000, // Default 1kg reorder
                    'lot_no' => null, // Let user add when they add inventory
                    'is_active' => true,
                    'notes' => null,
                ]);
                
                $this->command->info("✓ Created consumable for: {$seedEntry->common_name} ({$seedEntry->cultivar_name})");
                $created++;
            } catch (\Exception $e) {
                $this->command->error("✗ Failed to create consumable for: {$seedEntry->common_name} ({$seedEntry->cultivar_name})");
                $this->command->error("  Error: " . $e->getMessage());
            }
        }
        
        $this->command->info("\nCompleted! Created {$created} new seed consumables.");
        
        // Show summary of all seed consumables
        $totalSeedConsumables = Consumable::where('type', 'seed')->count();
        $this->command->info("Total seed consumables in database: {$totalSeedConsumables}");
    }
}
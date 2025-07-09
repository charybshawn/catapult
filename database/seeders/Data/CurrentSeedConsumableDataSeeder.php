<?php

namespace Database\Seeders\Data;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Consumable;
use App\Models\SeedEntry;
use App\Models\Supplier;

class CurrentSeedConsumableDataSeeder extends Seeder
{
    /**
     * Seed consumables with actual current inventory data
     * This represents the real state of seed inventory as manually entered
     */
    public function run(): void
    {
        $this->command->info('Seeding seed consumables with current inventory data...');
        
        // Define the actual seed consumable data based on current inventory
        $seedConsumables = [
            ['name' => 'Arugula (Arugula)', 'total' => 1000, 'consumed' => 502, 'unit' => 'g', 'lot' => 'AR2-01'],
            ['name' => 'Borage (Borage)', 'total' => 1000, 'consumed' => 540, 'unit' => 'g', 'lot' => 'BOR0Y'],
            ['name' => 'Kale (Red)', 'total' => 1000, 'consumed' => 538, 'unit' => 'g', 'lot' => 'KR3Y-01'],
            ['name' => 'Kohlrabi (Purple)', 'total' => 1000, 'consumed' => 591, 'unit' => 'g', 'lot' => 'KOH3-01'],
            ['name' => 'Kale (Green)', 'total' => 1000, 'consumed' => 388, 'unit' => 'g', 'lot' => 'KG2N2'],
            ['name' => 'Beet (Ruby)', 'total' => 1000, 'consumed' => 0, 'unit' => 'g', 'lot' => 'BER2L'],
            ['name' => 'Mustard (Oriental)', 'total' => 5000, 'consumed' => 2578, 'unit' => 'g', 'lot' => 'MO12'],
            ['name' => 'Radish (Red)', 'total' => 1000, 'consumed' => 636, 'unit' => 'g', 'lot' => 'RR4196LL'],
            ['name' => 'Broccoli (Broccoli)', 'total' => 5000, 'consumed' => 4834, 'unit' => 'g', 'lot' => 'RR4196LL'],
            ['name' => 'Broccoli (Broccoli)', 'total' => 1000, 'consumed' => 849, 'unit' => 'g', 'lot' => 'RR4196LL-2'],
            ['name' => 'Broccoli (Raab (Rapini))', 'total' => 1000, 'consumed' => 900, 'unit' => 'g', 'lot' => 'BR9'],
            ['name' => 'Beet (Ruby)', 'total' => 1000, 'consumed' => 800, 'unit' => 'g', 'lot' => 'BER2L-2'],
            ['name' => 'Swiss Chard (Yellow)', 'total' => 2000, 'consumed' => 0, 'unit' => 'g', 'lot' => '38124'],
            ['name' => 'Basil (Genovese)', 'total' => 1000, 'consumed' => 520, 'unit' => 'g', 'lot' => 'BAS8Y'],
            ['name' => 'Basil (Thai)', 'total' => 1000, 'consumed' => 20, 'unit' => 'g', 'lot' => 'BAST7L'],
            ['name' => 'Amaranth (Red)', 'total' => 450, 'consumed' => 110, 'unit' => 'g', 'lot' => '38637'],
            ['name' => 'Cress (Curly (Garden ))', 'total' => 125, 'consumed' => 85, 'unit' => 'g', 'lot' => 'CC9257SG'],
            ['name' => 'Fenugreek (Fenugreek)', 'total' => 125, 'consumed' => 45, 'unit' => 'g', 'lot' => 'F91S'],
            ['name' => 'Lentils, (Crimson)', 'total' => 125, 'consumed' => 70, 'unit' => 'g', 'lot' => 'LC121SU'],
            ['name' => 'Dill (Dill)', 'total' => 100, 'consumed' => 0, 'unit' => 'g', 'lot' => 'D1235LL'],
            ['name' => 'Mustard (Oriental)', 'total' => 5000, 'consumed' => 2150, 'unit' => 'g', 'lot' => 'MO12-2'],
            ['name' => 'Sunflower (Black Oilseed)', 'total' => 10000, 'consumed' => 4812, 'unit' => 'g', 'lot' => 'SFR16'],
            ['name' => 'Coriander (Coriander)', 'total' => 5000, 'consumed' => 750, 'unit' => 'g', 'lot' => 'COR3'],
            ['name' => 'Peas, (Speckled)', 'total' => 25000, 'consumed' => 16660, 'unit' => 'g', 'lot' => 'PS4M'],
            ['name' => 'Radish (Ruby Stem)', 'total' => 10000, 'consumed' => 500, 'unit' => 'g'],
        ];
        
        $created = 0;
        $updated = 0;
        
        foreach ($seedConsumables as $data) {
            // Extract common name and cultivar from the name format
            if (preg_match('/^(.+?)\s*\((.+?)\)$/', $data['name'], $matches)) {
                $commonName = trim($matches[1]);
                $cultivarName = trim($matches[2]);
                
                // Get default supplier for seeds
                $supplier = Supplier::where('name', "Mumm's Sprouting Seeds")->first();
                
                // Find the matching master seed catalog
                $masterSeedCatalog = \App\Models\MasterSeedCatalog::where('common_name', $commonName)->first();
                
                // Get the Seeds consumable type
                $seedsType = \App\Models\ConsumableType::where('name', 'Seeds')->first();
                
                // Create or update the consumable - independent of seed entries
                $consumable = Consumable::updateOrCreate(
                    [
                        'name' => $data['name'],
                        'type' => 'seed',
                        'lot_no' => $data['lot'] ?? null,
                    ],
                    [
                        'consumable_type_id' => $seedsType ? $seedsType->id : null,
                        'master_seed_catalog_id' => $masterSeedCatalog ? $masterSeedCatalog->id : null,
                        'supplier_id' => $supplier ? $supplier->id : null,
                        'initial_stock' => $data['total'], // Set initial stock to match total
                        'consumed_quantity' => $data['consumed'],
                        'total_quantity' => $data['total'],
                        'quantity_unit' => $data['unit'],
                        'quantity_per_unit' => 1,
                        'unit' => 'unit',
                        'cost_per_unit' => null,
                        'restock_threshold' => 250,
                        'restock_quantity' => 1000,
                        'is_active' => true,
                        'notes' => 'Current inventory as of ' . date('Y-m-d'),
                    ]
                );
                
                if ($consumable->wasRecentlyCreated) {
                    $created++;
                    $this->command->info("Created: {$data['name']} - {$data['total']}{$data['unit']} (consumed: {$data['consumed']}{$data['unit']})");
                } else {
                    $updated++;
                    $this->command->comment("Updated: {$data['name']} - {$data['total']}{$data['unit']} (consumed: {$data['consumed']}{$data['unit']})");
                }
            }
        }
        
        $this->command->info("\nSeeding completed!");
        $this->command->info("Created: {$created} consumables");
        $this->command->info("Updated: {$updated} consumables");
        $this->command->info("Total seed consumables with inventory: " . 
            Consumable::where('type', 'seed')->where('total_quantity', '>', 0)->count()
        );
    }
}
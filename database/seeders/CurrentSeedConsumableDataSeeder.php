<?php

namespace Database\Seeders;

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
            ['name' => 'Arugula (Arugula)', 'total' => 1000, 'consumed' => 502, 'unit' => 'g'],
            ['name' => 'Borage (Borage)', 'total' => 1000, 'consumed' => 540, 'unit' => 'g'],
            ['name' => 'Kale (Red)', 'total' => 1000, 'consumed' => 538, 'unit' => 'g'],
            ['name' => 'Kohlrabi (Purple)', 'total' => 1000, 'consumed' => 591, 'unit' => 'g'],
            ['name' => 'Kale (Green)', 'total' => 1000, 'consumed' => 388, 'unit' => 'g'],
            ['name' => 'Beet (Ruby)', 'total' => 1000, 'consumed' => 0, 'unit' => 'kg'],
            ['name' => 'Mustard (Oriental)', 'total' => 5000, 'consumed' => 2578, 'unit' => 'g'],
            ['name' => 'Radish (Red)', 'total' => 1000, 'consumed' => 636, 'unit' => 'kg'],
            ['name' => 'Broccoli (Broccoli)', 'total' => 5000, 'consumed' => 4834, 'unit' => 'g', 'lot' => 'LOT-001'],
            ['name' => 'Broccoli (Broccoli)', 'total' => 1000, 'consumed' => 849, 'unit' => 'g', 'lot' => 'LOT-002'],
            ['name' => 'Broccoli (Raab (Rapini))', 'total' => 1000, 'consumed' => 900, 'unit' => 'g'],
            ['name' => 'Beet (Ruby)', 'total' => 1, 'consumed' => 0.8, 'unit' => 'kg', 'lot' => 'SMALL-BATCH'],
            ['name' => 'Swiss Chard (Yellow)', 'total' => 2000, 'consumed' => 0, 'unit' => 'g'],
            ['name' => 'Basil (Genovese)', 'total' => 1000, 'consumed' => 520, 'unit' => 'g'],
            ['name' => 'Basil (Thai)', 'total' => 1000, 'consumed' => 20, 'unit' => 'g'],
            ['name' => 'Amaranth (Red)', 'total' => 450, 'consumed' => 110, 'unit' => 'g'],
            ['name' => 'Cress (Curly (Garden ))', 'total' => 125, 'consumed' => 85, 'unit' => 'g'],
            ['name' => 'Fenugreek (Fenugreek)', 'total' => 125, 'consumed' => 45, 'unit' => 'g'],
            ['name' => 'Lentils, (Crimson)', 'total' => 125, 'consumed' => 70, 'unit' => 'g'],
            ['name' => 'Dill (Dill)', 'total' => 100, 'consumed' => 0, 'unit' => 'g'],
            ['name' => 'Mustard (Oriental)', 'total' => 5000, 'consumed' => 2150, 'unit' => 'g', 'lot' => 'BATCH-2'],
            ['name' => 'Sunflower (Black Oilseed)', 'total' => 10000, 'consumed' => 4812, 'unit' => 'g'],
            ['name' => 'Coriander (Coriander)', 'total' => 5000, 'consumed' => 750, 'unit' => 'g'],
            ['name' => 'Peas, (Speckled)', 'total' => 25000, 'consumed' => 16660, 'unit' => 'g'],
            ['name' => 'Radish (Ruby Stem)', 'total' => 10000, 'consumed' => 500, 'unit' => 'g'],
        ];
        
        $created = 0;
        $updated = 0;
        
        foreach ($seedConsumables as $data) {
            // Extract common name and cultivar from the name format
            if (preg_match('/^(.+?)\s*\((.+?)\)$/', $data['name'], $matches)) {
                $commonName = trim($matches[1]);
                $cultivarName = trim($matches[2]);
                
                // // Find the seed entry
                // $seedEntry = SeedEntry::where('common_name', $commonName)
                //     ->where('cultivar_name', $cultivarName)
                //     ->first();
                
                // if (!$seedEntry) {
                //     $this->command->warn("Seed entry not found for: {$data['name']}");
                //     continue;
                // }
                
                // Create or update the consumable
                $consumable = Consumable::updateOrCreate(
                    [
                        'seed_entry_id' => $seedEntry->id,
                        'type' => 'seed',
                        'lot_no' => $data['lot'] ?? null,
                    ],
                    [
                        'name' => $data['name'],
                        'supplier_id' => $seedEntry->supplier_id,
                        'initial_stock' => 1,
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
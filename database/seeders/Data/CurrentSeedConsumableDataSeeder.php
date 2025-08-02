<?php

namespace Database\Seeders\Data;

use App\Models\Consumable;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class CurrentSeedConsumableDataSeeder extends Seeder
{
    /**
     * Seed consumables with actual current inventory data
     * This represents the real state of seed and soil inventory as manually entered
     */
    public function run(): void
    {
        $this->command->info('Seeding seed and soil consumables with current inventory data...');

        // Define the actual consumable data based on current inventory
        $consumables = [
            // Soil consumables
            ['name' => 'Pro Mix HP', 'total' => 428, 'consumed' => 0, 'unit' => 'l', 'type' => 'soil'],

            // Seed consumables - corrected to match master catalog/cultivar data
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
            // Skip 'Lentils, (Crimson)' and 'Dill (Dill)' as they don't exist in master catalog
            ['name' => 'Mustard (Oriental)', 'total' => 5000, 'consumed' => 2150, 'unit' => 'g', 'lot' => 'MO12-2'],
            ['name' => 'Sunflower (Black Oilseed)', 'total' => 10000, 'consumed' => 4812, 'unit' => 'g', 'lot' => 'SFR16'],
            ['name' => 'Coriander (Coriander)', 'total' => 5000, 'consumed' => 750, 'unit' => 'g', 'lot' => 'COR3'],
            ['name' => 'Peas (Speckled)', 'total' => 25000, 'consumed' => 16660, 'unit' => 'g', 'lot' => 'PS4M'],
            ['name' => 'Radish (Ruby Stem)', 'total' => 10000, 'consumed' => 500, 'unit' => 'g'],
        ];

        $created = 0;
        $updated = 0;
        $cultivarMatches = 0;
        $catalogMatches = 0;

        foreach ($consumables as $data) {
            $consumableType = $data['type'] ?? 'seed'; // Default to seed if not specified

            // Handle soil consumables differently
            if ($consumableType === 'soil') {
                $this->createSoilConsumable($data);
                $created++; // Assume it's created for simplicity

                continue;
            }

            // Extract common name and cultivar from the name format for seeds
            if (preg_match('/^(.+?)\s*\((.+?)\)$/', $data['name'], $matches)) {
                $commonName = trim($matches[1]);
                $cultivarName = trim($matches[2]);

                // Clean up common name - remove trailing commas and normalize
                $commonName = rtrim($commonName, ',');
                $commonName = trim($commonName);

                // Create missing catalog entries if they don't exist
                $this->ensureCatalogExists($commonName, $cultivarName);

                // Get default supplier for seeds
                $supplier = Supplier::where('name', "Mumm's Sprouting Seeds")->first();

                // Find the matching master seed catalog
                $masterSeedCatalog = \App\Models\MasterSeedCatalog::where('common_name', $commonName)->first();

                // Find the matching master cultivar
                $masterCultivar = null;
                if ($masterSeedCatalog) {
                    $masterCultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $masterSeedCatalog->id)
                        ->where('cultivar_name', $cultivarName)
                        ->first();

                    if (! $masterCultivar) {
                        $this->command->warn("No cultivar found for '{$cultivarName}' in '{$commonName}' catalog");
                    }
                } else {
                    $this->command->warn("No master seed catalog found for '{$commonName}'");
                }

                // Track statistics
                if ($masterSeedCatalog) {
                    $catalogMatches++;
                }
                if ($masterCultivar) {
                    $cultivarMatches++;
                }

                // Get the Seeds consumable type
                $seedsType = \App\Models\ConsumableType::where('name', 'Seeds')->first();

                // Get the gram unit for seeds
                $gramUnit = \App\Models\ConsumableUnit::where('code', 'gram')->first();

                // Create or update the consumable - independent of seed entries
                $consumable = Consumable::updateOrCreate(
                    [
                        'name' => $data['name'],
                        'lot_no' => $data['lot'] ?? null,
                    ],
                    [
                        'consumable_type_id' => $seedsType ? $seedsType->id : null,
                        'consumable_unit_id' => $gramUnit ? $gramUnit->id : null,
                        'master_seed_catalog_id' => $masterSeedCatalog ? $masterSeedCatalog->id : null,
                        'master_cultivar_id' => $masterCultivar ? $masterCultivar->id : null,
                        'supplier_id' => $supplier ? $supplier->id : null,
                        'initial_stock' => $data['total'], // Set initial stock to match total
                        'consumed_quantity' => $data['consumed'],
                        'total_quantity' => $data['total'],
                        'quantity_unit' => $data['unit'],
                        'quantity_per_unit' => 1,
                        'cost_per_unit' => null,
                        'restock_threshold' => 250,
                        'restock_quantity' => 1000,
                        'is_active' => true,
                        'notes' => 'Current inventory as of '.date('Y-m-d'),
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
        $seedCount = count(array_filter($consumables, fn ($item) => ($item['type'] ?? 'seed') === 'seed'));
        $this->command->info("Catalog matches: {$catalogMatches}/".$seedCount);
        $this->command->info("Cultivar matches: {$cultivarMatches}/".$seedCount);
        $this->command->info('Total seed consumables with inventory: '.
            Consumable::whereHas('consumableType', function ($query) {
                $query->where('code', 'seed');
            })->where('total_quantity', '>', 0)->count()
        );
    }

    /**
     * Ensure a master seed catalog and cultivar exist for the given names
     */
    private function ensureCatalogExists(string $commonName, string $cultivarName): void
    {
        // Check if catalog exists
        $catalog = \App\Models\MasterSeedCatalog::where('common_name', $commonName)->first();

        if (! $catalog) {
            // Create missing catalog entry
            $catalog = \App\Models\MasterSeedCatalog::create([
                'common_name' => $commonName,
                'cultivars' => json_encode([$cultivarName]),
                'is_active' => true,
            ]);
            $this->command->info("Created missing catalog entry: {$commonName}");
        }

        // Check if cultivar exists
        $cultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $catalog->id)
            ->where('cultivar_name', $cultivarName)
            ->first();

        if (! $cultivar) {
            // Create missing cultivar entry
            \App\Models\MasterCultivar::create([
                'master_seed_catalog_id' => $catalog->id,
                'cultivar_name' => $cultivarName,
                'is_active' => true,
            ]);
            $this->command->info("Created missing cultivar: {$cultivarName} for {$commonName}");
        }
    }

    /**
     * Create or update a soil consumable
     */
    private function createSoilConsumable(array $data): void
    {
        // Get the Soil consumable type
        $soilType = \App\Models\ConsumableType::where('code', 'soil')->first();

        // Get the liter unit for soil
        $literUnit = \App\Models\ConsumableUnit::where('code', 'liter')->first();

        // Get default soil supplier
        $supplier = Supplier::where('name', 'Ecoline')->first();

        // Create or update the soil consumable
        $consumable = Consumable::updateOrCreate(
            [
                'name' => $data['name'],
            ],
            [
                'consumable_type_id' => $soilType ? $soilType->id : null,
                'consumable_unit_id' => $literUnit ? $literUnit->id : null,
                'supplier_id' => $supplier ? $supplier->id : null,
                'initial_stock' => $data['total'],
                'consumed_quantity' => $data['consumed'],
                'total_quantity' => $data['total'],
                'quantity_unit' => $data['unit'],
                'quantity_per_unit' => 1,
                'cost_per_unit' => null,
                'restock_threshold' => 50,
                'restock_quantity' => 250,
                'is_active' => true,
                'notes' => 'Current soil inventory as of '.date('Y-m-d'),
            ]
        );

        if ($consumable->wasRecentlyCreated) {
            $this->command->info("Created: {$data['name']} - {$data['total']}{$data['unit']} (consumed: {$data['consumed']}{$data['unit']})");
        } else {
            $this->command->comment("Updated: {$data['name']} - {$data['total']}{$data['unit']} (consumed: {$data['consumed']}{$data['unit']})");
        }
    }
}

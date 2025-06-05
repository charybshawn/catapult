<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\SeedCultivar;
use App\Models\Supplier;
use App\Models\Consumable;
use App\Models\RecipeWateringSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RealWorldRecipesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating suppliers...');
        $this->createSuppliers();
        
        $this->command->info('Creating soil consumables...');
        $this->createSoils();
        
        $this->command->info('Creating seed cultivars and consumables...');
        $this->createSeedsAndCultivars();
        
        $this->command->info('Creating recipes...');
        $this->createRecipes();
        
        $this->command->info('Real-world recipes, seeds, and soils have been created!');
    }
    
    /**
     * Create realistic supplier data.
     */
    private function createSuppliers(): void
    {
        $suppliers = [
            [
                'name' => 'True Leaf Market',
                'type' => 'seed',
                'contact_email' => 'sales@trueleafmarket.com',
                'contact_phone' => '801-491-8700',
                'notes' => 'High-quality microgreen seeds and supplies',
            ],
            [
                'name' => 'Johnny\'s Selected Seeds',
                'type' => 'seed',
                'contact_email' => 'customerservice@johnnyseeds.com',
                'contact_phone' => '877-564-6697',
                'notes' => 'Premium seeds and agricultural supplies',
            ],
            [
                'name' => 'Mumm\'s Sprouting Seeds',
                'type' => 'seed',
                'contact_email' => 'info@sprouting.com',
                'contact_phone' => '306-747-2935',
                'notes' => 'Canadian organic seed supplier specializing in microgreens and sprouts',
            ],
            [
                'name' => 'Premier Tech Horticulture',
                'type' => 'soil',
                'contact_email' => 'customer.service@premiertech.com',
                'contact_phone' => '855-867-5407',
                'notes' => 'Pro-Mix soil producer',
            ],
            [
                'name' => 'Sunlight Supply',
                'type' => 'consumable',
                'contact_email' => 'info@sunlightsupply.com',
                'contact_phone' => '360-823-6903',
                'notes' => 'Growing equipment, containers, and hydroponic supplies',
            ],
        ];
        
        foreach ($suppliers as $supplierData) {
            Supplier::firstOrCreate(
                ['name' => $supplierData['name']],
                $supplierData
            );
        }
    }
    
    /**
     * Create realistic soil consumables.
     */
    private function createSoils(): void
    {
        $soilSupplier = Supplier::where('type', 'soil')->first();
        
        $soils = [
            [
                'name' => 'Pro-Mix Organic Seed Starting Mix',
                'type' => 'soil',
                'supplier_id' => $soilSupplier->id,
                'initial_stock' => 10,
                'consumed_quantity' => 0,
                'unit' => 'bag',
                'quantity_per_unit' => 20,
                'quantity_unit' => 'l',
                'restock_threshold' => 2,
                'restock_quantity' => 5,
                'cost_per_unit' => 14.99,
                'notes' => 'Premium organic seed starting mix, excellent moisture retention',
                'is_active' => true,
            ],
            [
                'name' => 'Coco Coir Growing Medium',
                'type' => 'soil',
                'supplier_id' => $soilSupplier->id,
                'initial_stock' => 8,
                'consumed_quantity' => 0,
                'unit' => 'bag',
                'quantity_per_unit' => 10,
                'quantity_unit' => 'l',
                'restock_threshold' => 2,
                'restock_quantity' => 10,
                'cost_per_unit' => 8.95,
                'notes' => 'Compressed coconut coir bricks, expands when hydrated',
                'is_active' => true,
            ],
            [
                'name' => 'Vermiculite Medium Grade',
                'type' => 'soil',
                'supplier_id' => $soilSupplier->id,
                'initial_stock' => 5,
                'consumed_quantity' => 0,
                'unit' => 'bag',
                'quantity_per_unit' => 8,
                'quantity_unit' => 'l',
                'restock_threshold' => 1,
                'restock_quantity' => 4,
                'cost_per_unit' => 12.50,
                'notes' => 'Used to improve moisture retention in growing medium',
                'is_active' => true,
            ],
        ];
        
        foreach ($soils as $soilData) {
            Consumable::firstOrCreate(
                ['name' => $soilData['name'], 'type' => 'soil'],
                $soilData
            );
        }
    }
    
    /**
     * Create realistic seed cultivars and seed consumables.
     */
    private function createSeedsAndCultivars(): void
    {
        $seedSuppliers = Supplier::where('type', 'seed')->get();
        
        $seeds = [
            [
                'name' => 'Sunflower - Black Oil',
                'variety_data' => [
                    'name' => 'Sunflower - Black Oil',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                'consumable_data' => [
                    'type' => 'seed',
                    'supplier_id' => $seedSuppliers->random()->id,
                    'initial_stock' => 5,
                    'consumed_quantity' => 0,
                    'unit' => 'bag',
                    'quantity_per_unit' => 1000,
                    'quantity_unit' => 'g',
                    'restock_threshold' => 1,
                    'restock_quantity' => 2,
                    'cost_per_unit' => 19.99,
                    'is_active' => true,
                ],
            ],
            [
                'name' => 'Pea - Speckled',
                'variety_data' => [
                    'name' => 'Pea - Speckled',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                'consumable_data' => [
                    'type' => 'seed',
                    'supplier_id' => $seedSuppliers->random()->id,
                    'initial_stock' => 5,
                    'consumed_quantity' => 0,
                    'unit' => 'bag',
                    'quantity_per_unit' => 1000,
                    'quantity_unit' => 'g',
                    'restock_threshold' => 1,
                    'restock_quantity' => 2,
                    'cost_per_unit' => 15.99,
                    'is_active' => true,
                ],
            ],
            [
                'name' => 'Radish - Daikon',
                'variety_data' => [
                    'name' => 'Radish - Daikon',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                'consumable_data' => [
                    'type' => 'seed',
                    'supplier_id' => $seedSuppliers->random()->id,
                    'initial_stock' => 5,
                    'consumed_quantity' => 0,
                    'unit' => 'bag',
                    'quantity_per_unit' => 500,
                    'quantity_unit' => 'g',
                    'restock_threshold' => 1,
                    'restock_quantity' => 2,
                    'cost_per_unit' => 12.99,
                    'is_active' => true,
                ],
            ],
            [
                'name' => 'Broccoli',
                'variety_data' => [
                    'name' => 'Broccoli',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                'consumable_data' => [
                    'type' => 'seed',
                    'supplier_id' => $seedSuppliers->random()->id,
                    'initial_stock' => 5,
                    'consumed_quantity' => 0,
                    'unit' => 'bag',
                    'quantity_per_unit' => 500,
                    'quantity_unit' => 'g',
                    'restock_threshold' => 1,
                    'restock_quantity' => 2,
                    'cost_per_unit' => 14.95,
                    'is_active' => true,
                ],
            ],
            [
                'name' => 'Amaranth - Red',
                'variety_data' => [
                    'name' => 'Amaranth - Red',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                'consumable_data' => [
                    'type' => 'seed',
                    'supplier_id' => $seedSuppliers->random()->id,
                    'initial_stock' => 3,
                    'consumed_quantity' => 0,
                    'unit' => 'bag',
                    'quantity_per_unit' => 250,
                    'quantity_unit' => 'g',
                    'restock_threshold' => 1,
                    'restock_quantity' => 1,
                    'cost_per_unit' => 8.99,
                    'is_active' => true,
                ],
            ],
            [
                'name' => 'Basil - Genovese',
                'variety_data' => [
                    'name' => 'Basil - Genovese',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                'consumable_data' => [
                    'type' => 'seed',
                    'supplier_id' => $seedSuppliers->random()->id,
                    'initial_stock' => 3,
                    'consumed_quantity' => 0,
                    'unit' => 'bag',
                    'quantity_per_unit' => 250,
                    'quantity_unit' => 'g',
                    'restock_threshold' => 1,
                    'restock_quantity' => 1,
                    'cost_per_unit' => 10.99,
                    'is_active' => true,
                ],
            ],
            [
                'name' => 'Mustard - Red',
                'variety_data' => [
                    'name' => 'Mustard - Red',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                'consumable_data' => [
                    'type' => 'seed',
                    'supplier_id' => $seedSuppliers->random()->id,
                    'initial_stock' => 3,
                    'consumed_quantity' => 0,
                    'unit' => 'bag',
                    'quantity_per_unit' => 250,
                    'quantity_unit' => 'g',
                    'restock_threshold' => 1,
                    'restock_quantity' => 1,
                    'cost_per_unit' => 9.49,
                    'is_active' => true,
                ],
            ],
            [
                'name' => 'Kale - Red Russian',
                'variety_data' => [
                    'name' => 'Kale - Red Russian',
                    'crop_type' => 'microgreens',
                    'is_active' => true,
                ],
                'consumable_data' => [
                    'type' => 'seed',
                    'supplier_id' => $seedSuppliers->random()->id,
                    'initial_stock' => 3,
                    'consumed_quantity' => 0,
                    'unit' => 'bag',
                    'quantity_per_unit' => 250,
                    'quantity_unit' => 'g',
                    'restock_threshold' => 1,
                    'restock_quantity' => 1,
                    'cost_per_unit' => 12.49,
                    'is_active' => true,
                ],
            ],
        ];
        
        foreach ($seeds as $seedData) {
            // Create seed cultivar
            $seedCultivar = SeedCultivar::firstOrCreate(
                ['name' => $seedData['variety_data']['name']],
                $seedData['variety_data']
            );
            
            // Create seed consumable (no longer linked to cultivar directly)
            $consumableData = $seedData['consumable_data'];
            $consumableData['name'] = $seedData['name'];
            // Remove the seed_variety_id reference
            unset($consumableData['seed_variety_id']);
            
            Consumable::firstOrCreate(
                ['name' => $consumableData['name'], 'type' => 'seed'],
                $consumableData
            );
        }
    }
    
    /**
     * Create realistic recipes for different seed cultivars.
     */
    private function createRecipes(): void
    {
        $seedConsumables = Consumable::where('type', 'seed')->get();
        $soilConsumables = Consumable::where('type', 'soil')->get();
        
        $recipeData = [
            'Sunflower - Black Oil' => [
                'seed_soak_hours' => 8,
                'germination_days' => 3,
                'blackout_days' => 2,
                'light_days' => 6,
                'seed_density_grams_per_tray' => 180,
                'expected_yield_grams' => 350,
                'notes' => "## Planting Notes\nSoak seeds for 8 hours before planting. Use a spray bottle to thoroughly moisten soil before spreading seeds.\n\n## Germination Notes\nKeep moist but not soaking wet. Cover with a second tray to create darkness.\n\n## Blackout Notes\nStack weight on top to encourage strong root development. Keep moist with bottom watering.\n\n## Light Notes\nAfter blackout period, move to light. Water daily. Harvest when first true leaves appear between cotyledons.",
                'watering_schedule' => [
                    ['day_number' => 1, 'water_amount_ml' => 500, 'watering_method' => 'top', 'notes' => 'Initial watering after planting'],
                    ['day_number' => 3, 'water_amount_ml' => 350, 'watering_method' => 'bottom', 'notes' => 'Start of blackout'],
                    ['day_number' => 4, 'water_amount_ml' => 350, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 5, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => 'Start of light phase'],
                    ['day_number' => 6, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 7, 'water_amount_ml' => 450, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 8, 'water_amount_ml' => 450, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 9, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 10, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 11, 'water_amount_ml' => 0, 'watering_method' => 'none', 'notes' => 'Prepare for harvest'],
                ]
            ],
            'Pea - Speckled' => [
                'seed_soak_hours' => 12,
                'germination_days' => 3,
                'blackout_days' => 3,
                'light_days' => 8,
                'seed_density_grams_per_tray' => 200,
                'expected_yield_grams' => 400,
                'notes' => "## Planting Notes\nSoak seeds for 12 hours before planting. Seeds should be visibly swollen. Use a spray bottle to thoroughly moisten soil before spreading seeds.\n\n## Germination Notes\nKeep moist but not soaking wet. Cover with a second tray to create darkness.\n\n## Blackout Notes\nStack weight on top to encourage strong root development. Keep moist with bottom watering.\n\n## Light Notes\nAfter blackout period, move to light. Water daily. Harvest when tendrils start to form and greens reach 3-4 inches tall.",
                'watering_schedule' => [
                    ['day_number' => 1, 'water_amount_ml' => 500, 'watering_method' => 'top', 'notes' => 'Initial watering after planting'],
                    ['day_number' => 3, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => 'Start of blackout'],
                    ['day_number' => 4, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 5, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 6, 'water_amount_ml' => 450, 'watering_method' => 'bottom', 'notes' => 'Start of light phase'],
                    ['day_number' => 7, 'water_amount_ml' => 450, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 8, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 9, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 10, 'water_amount_ml' => 550, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 11, 'water_amount_ml' => 550, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 12, 'water_amount_ml' => 550, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 13, 'water_amount_ml' => 0, 'watering_method' => 'none', 'notes' => ''],
                    ['day_number' => 14, 'water_amount_ml' => 0, 'watering_method' => 'none', 'notes' => 'Prepare for harvest'],
                ]
            ],
            'Radish - Daikon' => [
                'seed_soak_hours' => 0,
                'germination_days' => 2,
                'blackout_days' => 1,
                'light_days' => 6,
                'seed_density_grams_per_tray' => 30,
                'expected_yield_grams' => 150,
                'notes' => "## Planting Notes\nNo need to pre-soak. Spread seeds evenly, they're tiny so be careful not to overseed.\n\n## Germination Notes\nKeep moist but not soaking wet. Seeds germinate quickly, usually within 24-48 hours.\n\n## Blackout Notes\nShort blackout period to strengthen stems.\n\n## Light Notes\nMove to light after brief blackout. Radish grows quickly - keep well watered and harvest when first true leaves appear.",
                'watering_schedule' => [
                    ['day_number' => 1, 'water_amount_ml' => 500, 'watering_method' => 'top', 'notes' => 'Initial watering after planting'],
                    ['day_number' => 3, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => 'Start of blackout'],
                    ['day_number' => 4, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => 'Start of light phase'],
                    ['day_number' => 5, 'water_amount_ml' => 450, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 6, 'water_amount_ml' => 450, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 7, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 8, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 9, 'water_amount_ml' => 0, 'watering_method' => 'none', 'notes' => 'Prepare for harvest'],
                ]
            ],
            'Broccoli' => [
                'seed_soak_hours' => 0,
                'germination_days' => 2,
                'blackout_days' => 1,
                'light_days' => 8,
                'seed_density_grams_per_tray' => 30,
                'expected_yield_grams' => 140,
                'notes' => "## Planting Notes\nNo need to pre-soak. Spread seeds evenly. These are small seeds so be careful not to overseed.\n\n## Germination Notes\nKeep moist but not soaking wet. Seeds germinate quickly.\n\n## Blackout Notes\nShort blackout period just to strengthen stems.\n\n## Light Notes\nMove to light after brief blackout. Broccoli microgreens have a mild, slightly spicy flavor. Harvest when cotyledons are fully expanded and first true leaves are beginning to form.",
                'watering_schedule' => [
                    ['day_number' => 1, 'water_amount_ml' => 500, 'watering_method' => 'top', 'notes' => 'Initial watering after planting'],
                    ['day_number' => 3, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => 'Start of blackout'],
                    ['day_number' => 4, 'water_amount_ml' => 400, 'watering_method' => 'bottom', 'notes' => 'Start of light phase'],
                    ['day_number' => 5, 'water_amount_ml' => 450, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 6, 'water_amount_ml' => 450, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 7, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 8, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 9, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 10, 'water_amount_ml' => 500, 'watering_method' => 'bottom', 'notes' => ''],
                    ['day_number' => 11, 'water_amount_ml' => 0, 'watering_method' => 'none', 'notes' => 'Prepare for harvest'],
                ]
            ],
        ];
        
        foreach ($recipeData as $seedName => $data) {
            $seedConsumable = $seedConsumables->firstWhere('name', $seedName);
            
            if ($seedConsumable) {
                // Create recipe
                $recipe = Recipe::firstOrCreate(
                    ['name' => "{$seedName} Recipe"],
                    [
                        'seed_cultivar_id' => SeedCultivar::where('name', 'like', '%' . explode(' ', $seedName)[0] . '%')->first()?->id,
                        'seed_consumable_id' => $seedConsumable->id,
                        'soil_consumable_id' => $soilConsumables->random()->id,
                        'seed_soak_hours' => $data['seed_soak_hours'],
                        'germination_days' => $data['germination_days'],
                        'blackout_days' => $data['blackout_days'],
                        'light_days' => $data['light_days'],
                        'seed_density_grams_per_tray' => $data['seed_density_grams_per_tray'],
                        'expected_yield_grams' => $data['expected_yield_grams'],
                        'notes' => $data['notes'],
                        'is_active' => true,
                    ]
                );
                
                // Create watering schedule
                if (isset($data['watering_schedule'])) {
                    foreach ($data['watering_schedule'] as $wateringData) {
                        RecipeWateringSchedule::firstOrCreate(
                            [
                                'recipe_id' => $recipe->id,
                                'day_number' => $wateringData['day_number']
                            ],
                            [
                                'water_amount_ml' => $wateringData['water_amount_ml'],
                                'watering_method' => $wateringData['watering_method'],
                                'needs_liquid_fertilizer' => false,
                                'notes' => $wateringData['notes'],
                            ]
                        );
                    }
                }
            }
        }
    }
} 
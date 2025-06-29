<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SeedEntry;
use App\Models\Supplier;
use App\Models\SeedVariation;

class CurrentSeedEntryDataSeeder extends Seeder
{
    /**
     * Seed entries with actual current data from the database
     * This must run before CurrentSeedConsumableDataSeeder
     */
    public function run(): void
    {
        $this->command->info('Seeding seed entries with current data...');
        
        // First ensure we have the supplier
        $supplier = Supplier::firstOrCreate(
            ['name' => "Mumm's Sprouting Seeds"],
            [
                'type' => 'seed',
                'contact_email' => null,
                'contact_phone' => null,
                'is_active' => true,
                'notes' => 'Primary seed supplier'
            ]
        );
        
        $this->command->info("Using supplier: {$supplier->name} (ID: {$supplier->id})");
        
        // Define all seed entries based on current database data
        $seedEntries = [
            ['id' => 1, 'cultivar_name' => 'Clover', 'common_name' => 'Alfalfa'],
            ['id' => 2, 'cultivar_name' => 'Arugula', 'common_name' => 'Arugula'],
            ['id' => 3, 'cultivar_name' => 'Rocky Wild', 'common_name' => 'Arugula'],
            ['id' => 4, 'cultivar_name' => 'Red', 'common_name' => 'Amaranth'],
            ['id' => 5, 'cultivar_name' => 'Barley', 'common_name' => 'Barley'],
            ['id' => 6, 'cultivar_name' => 'Genovese', 'common_name' => 'Basil'],
            ['id' => 7, 'cultivar_name' => 'Purple', 'common_name' => 'Basil'],
            ['id' => 8, 'cultivar_name' => 'Thai', 'common_name' => 'Basil'],
            ['id' => 9, 'cultivar_name' => 'Mung', 'common_name' => 'Bean'],
            ['id' => 10, 'cultivar_name' => 'Ruby', 'common_name' => 'Beet'],
            ['id' => 11, 'cultivar_name' => 'Bull\'s Blood', 'common_name' => 'Beet'],
            ['id' => 12, 'cultivar_name' => 'Borage', 'common_name' => 'Borage'],
            ['id' => 13, 'cultivar_name' => 'Pink', 'common_name' => 'Beet'],
            ['id' => 14, 'cultivar_name' => 'Broccoli', 'common_name' => 'Broccoli'],
            ['id' => 15, 'cultivar_name' => 'Raab (Rapini)', 'common_name' => 'Broccoli'],
            ['id' => 16, 'cultivar_name' => '(Pak Choi)', 'common_name' => 'Bok Choy'],
            ['id' => 17, 'cultivar_name' => 'Red', 'common_name' => 'Cabbage'],
            ['id' => 18, 'cultivar_name' => 'Yellow', 'common_name' => 'Swiss Chard'],
            ['id' => 19, 'cultivar_name' => 'Red', 'common_name' => 'Swiss Chard'],
            ['id' => 20, 'cultivar_name' => 'Black', 'common_name' => 'Chia'],
            ['id' => 21, 'cultivar_name' => 'Coriander', 'common_name' => 'Coriander'],
            ['id' => 22, 'cultivar_name' => 'Garlic', 'common_name' => 'Cress'],
            ['id' => 23, 'cultivar_name' => 'Curly (Garden )', 'common_name' => 'Cress'],
            ['id' => 24, 'cultivar_name' => 'Dill', 'common_name' => 'Dill'],
            ['id' => 25, 'cultivar_name' => 'Fenugreek', 'common_name' => 'Fenugreek'],
            ['id' => 26, 'cultivar_name' => 'Flax', 'common_name' => 'Flax'],
            ['id' => 27, 'cultivar_name' => 'Blend', 'common_name' => 'Brilliant'],
            ['id' => 28, 'cultivar_name' => 'Buckwheat', 'common_name' => 'Buckwheat'],
            ['id' => 29, 'cultivar_name' => 'Crimson', 'common_name' => 'Lentils,'],
            ['id' => 30, 'cultivar_name' => 'French', 'common_name' => 'Lentils,'],
            ['id' => 31, 'cultivar_name' => 'Carrot', 'common_name' => 'Carrot'],
            ['id' => 32, 'cultivar_name' => 'Celery', 'common_name' => 'Celery'],
            ['id' => 33, 'cultivar_name' => 'Chervil', 'common_name' => 'Chervil'],
            ['id' => 34, 'cultivar_name' => 'Chicory', 'common_name' => 'Chicory'],
            ['id' => 35, 'cultivar_name' => 'Fennel', 'common_name' => 'Fennel'],
            ['id' => 36, 'cultivar_name' => 'Green', 'common_name' => 'Kale'],
            ['id' => 37, 'cultivar_name' => 'Red', 'common_name' => 'Kale'],
            ['id' => 38, 'cultivar_name' => 'Purple', 'common_name' => 'Kale'],
            ['id' => 39, 'cultivar_name' => 'Purple', 'common_name' => 'Kohlrabi'],
            ['id' => 40, 'cultivar_name' => 'Black', 'common_name' => 'Lentils,'],
            ['id' => 41, 'cultivar_name' => 'Small Green', 'common_name' => 'Lentils,'],
            ['id' => 42, 'cultivar_name' => 'Large Green', 'common_name' => 'Lentils,'],
            ['id' => 43, 'cultivar_name' => 'Mizuna', 'common_name' => 'Mustard'],
            ['id' => 44, 'cultivar_name' => 'Oriental', 'common_name' => 'Mustard'],
            ['id' => 45, 'cultivar_name' => 'Red', 'common_name' => 'Mustard'],
            ['id' => 46, 'cultivar_name' => 'Crimson', 'common_name' => 'Clover'],
            ['id' => 47, 'cultivar_name' => 'Red', 'common_name' => 'Clover'],
            ['id' => 48, 'cultivar_name' => 'Collard', 'common_name' => 'Collard'],
            ['id' => 49, 'cultivar_name' => 'Corn Salad', 'common_name' => 'Corn Salad'],
            ['id' => 50, 'cultivar_name' => 'Cranberry', 'common_name' => 'Bean'],
            ['id' => 51, 'cultivar_name' => 'Brown', 'common_name' => 'Mustard'],
            ['id' => 52, 'cultivar_name' => 'Tat Soi', 'common_name' => 'Mustard'],
            ['id' => 53, 'cultivar_name' => 'Tokyo Bekana', 'common_name' => 'Mustard'],
            ['id' => 54, 'cultivar_name' => 'Yellow', 'common_name' => 'Mustard'],
            ['id' => 55, 'cultivar_name' => 'Komatsuna', 'common_name' => 'Komatsuna'],
            ['id' => 56, 'cultivar_name' => 'Purple', 'common_name' => 'Komatsuna'],
            ['id' => 57, 'cultivar_name' => 'Leek', 'common_name' => 'Leek'],
            ['id' => 58, 'cultivar_name' => 'Lemon Balm', 'common_name' => 'Lemon Balm'],
            ['id' => 59, 'cultivar_name' => 'Red Mizuna', 'common_name' => 'Mustard'],
            ['id' => 60, 'cultivar_name' => 'Ruby Streaks', 'common_name' => 'Mustard'],
            ['id' => 61, 'cultivar_name' => 'Beans', 'common_name' => 'Mung'],
            ['id' => 62, 'cultivar_name' => 'Emerald', 'common_name' => 'Nasturtium'],
            ['id' => 63, 'cultivar_name' => 'Red', 'common_name' => 'Nasturtium'],
            ['id' => 64, 'cultivar_name' => 'Hulless', 'common_name' => 'Oats,'],
            ['id' => 65, 'cultivar_name' => 'Onion', 'common_name' => 'Onion'],
            ['id' => 66, 'cultivar_name' => 'Parsley', 'common_name' => 'Parsley'],
            ['id' => 67, 'cultivar_name' => 'Oregon Giant', 'common_name' => 'Peas,'],
            ['id' => 68, 'cultivar_name' => 'Speckled', 'common_name' => 'Peas,'],
            ['id' => 69, 'cultivar_name' => 'Tendril', 'common_name' => 'Peas,'],
            ['id' => 70, 'cultivar_name' => 'Dwarf Grey Sugar', 'common_name' => 'Peas,'],
            ['id' => 71, 'cultivar_name' => 'Daikon', 'common_name' => 'Radish'],
            ['id' => 72, 'cultivar_name' => 'China Rose', 'common_name' => 'Radish'],
            ['id' => 73, 'cultivar_name' => 'Red', 'common_name' => 'Radish'],
            ['id' => 74, 'cultivar_name' => 'Ruby Stem', 'common_name' => 'Radish'],
            ['id' => 75, 'cultivar_name' => 'Endive', 'common_name' => 'Endive'],
            ['id' => 76, 'cultivar_name' => 'Beans', 'common_name' => 'Fava'],
            ['id' => 77, 'cultivar_name' => 'Green', 'common_name' => 'Peas,'],
            ['id' => 78, 'cultivar_name' => 'Garlic Chives', 'common_name' => 'Garlic Chives'],
            ['id' => 79, 'cultivar_name' => 'Yellow', 'common_name' => 'Peas,'],
            ['id' => 80, 'cultivar_name' => 'Popcorn', 'common_name' => 'Popcorn'],
            ['id' => 81, 'cultivar_name' => 'Red Shiso', 'common_name' => 'Red Shiso'],
            ['id' => 82, 'cultivar_name' => 'Black Oilseed', 'common_name' => 'Sunflower'],
            ['id' => 83, 'cultivar_name' => 'Hulled', 'common_name' => 'Sunflower'],
            ['id' => 84, 'cultivar_name' => 'Triton', 'common_name' => 'Radish'],
            ['id' => 85, 'cultivar_name' => 'Rainbow', 'common_name' => 'Radish'],
            ['id' => 86, 'cultivar_name' => 'Hard Red Spring', 'common_name' => 'Wheat'],
            ['id' => 87, 'cultivar_name' => 'Hard Red Winter', 'common_name' => 'Wheat'],
            ['id' => 88, 'cultivar_name' => 'Spigarello', 'common_name' => 'Spigarello'],
            ['id' => 89, 'cultivar_name' => 'Kamut', 'common_name' => 'Kamut'],
            ['id' => 90, 'cultivar_name' => 'Mellow Microgreen Mix', 'common_name' => 'Mellow Microgreen Mix'],
            ['id' => 91, 'cultivar_name' => 'Microgreen Salad Mix', 'common_name' => 'Microgreen Salad Mix'],
            ['id' => 92, 'cultivar_name' => 'Microgreen Blend', 'common_name' => 'Spicy'],
            ['id' => 93, 'cultivar_name' => 'Thyme', 'common_name' => 'Thyme'],
            ['id' => 94, 'cultivar_name' => 'Turnip', 'common_name' => 'Turnip'],
            ['id' => 95, 'cultivar_name' => 'Watercress', 'common_name' => 'Watercress'],
        ];
        
        $created = 0;
        $updated = 0;
        
        foreach ($seedEntries as $entry) {
            $seedEntry = SeedEntry::updateOrCreate(
                ['id' => $entry['id']],
                [
                    'cultivar_name' => $entry['cultivar_name'],
                    'common_name' => $entry['common_name'],
                    'supplier_id' => $supplier->id,
                    'supplier_sku' => strtolower(str_replace([' ', "'"], ['-', ''], $entry['cultivar_name'] . '-' . $entry['common_name'])),
                    'url' => 'https://sprouting.com/product/' . strtolower(str_replace([' ', "'"], ['-', ''], $entry['cultivar_name'] . '-' . $entry['common_name'])),
                    'image_url' => null,
                    'description' => null,
                    'tags' => [],
                    'is_active' => true,
                ]
            );
            
            if ($seedEntry->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }
        
        $this->command->info("Seed entries seeding completed!");
        $this->command->info("Created: {$created} entries");
        $this->command->info("Updated: {$updated} entries");
        
        // Add some sample variations for key entries (optional)
        $this->seedVariations();
    }
    
    /**
     * Seed some sample variations for key entries
     */
    private function seedVariations(): void
    {
        $this->command->info('Adding seed variations...');
        
        // Add variations for some key entries
        $variations = [
            // Arugula (ID: 2)
            2 => [
                ['size' => '125 grams', 'weight' => 0.125, 'price' => 7.99],
                ['size' => '500 grams', 'weight' => 0.500, 'price' => 19.99],
                ['size' => '1 kilogram', 'weight' => 1.000, 'price' => 34.99],
            ],
            // Broccoli (ID: 14)
            14 => [
                ['size' => '125 grams', 'weight' => 0.125, 'price' => 8.99],
                ['size' => '500 grams', 'weight' => 0.500, 'price' => 24.99],
                ['size' => '1 kilogram', 'weight' => 1.000, 'price' => 44.99],
                ['size' => '5 kilograms', 'weight' => 5.000, 'price' => 189.99],
            ],
            // Sunflower Black Oilseed (ID: 82)
            82 => [
                ['size' => '500 grams', 'weight' => 0.500, 'price' => 9.99],
                ['size' => '1 kilogram', 'weight' => 1.000, 'price' => 16.99],
                ['size' => '5 kilograms', 'weight' => 5.000, 'price' => 64.99],
                ['size' => '10 kilograms', 'weight' => 10.000, 'price' => 109.99],
            ],
            // Peas Speckled (ID: 68)
            68 => [
                ['size' => '500 grams', 'weight' => 0.500, 'price' => 8.99],
                ['size' => '1 kilogram', 'weight' => 1.000, 'price' => 14.99],
                ['size' => '5 kilograms', 'weight' => 5.000, 'price' => 54.99],
                ['size' => '25 kilograms', 'weight' => 25.000, 'price' => 224.99],
            ],
        ];
        
        $variationCount = 0;
        
        foreach ($variations as $seedEntryId => $sizes) {
            $seedEntry = SeedEntry::find($seedEntryId);
            if (!$seedEntry) continue;
            
            foreach ($sizes as $size) {
                SeedVariation::updateOrCreate(
                    [
                        'seed_entry_id' => $seedEntryId,
                        'size' => $size['size'],
                    ],
                    [
                        'weight_kg' => $size['weight'],
                        'original_weight_value' => $size['weight'] >= 1 ? $size['weight'] : $size['weight'] * 1000,
                        'original_weight_unit' => $size['weight'] >= 1 ? 'kg' : 'g',
                        'current_price' => $size['price'],
                        'is_available' => true,
                        'last_checked_at' => now(),
                    ]
                );
                $variationCount++;
            }
        }
        
        $this->command->info("Added {$variationCount} seed variations.");
    }
}
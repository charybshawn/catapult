<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SeedEntry;
use App\Models\Consumable;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SeedConsumableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating seed consumables from existing seed entries...');
        
        // Get all active seed entries
        $seedEntries = SeedEntry::where('is_active', true)
            ->with('supplier')
            ->get();
        
        if ($seedEntries->isEmpty()) {
            $this->command->warn('No active seed entries found in the database.');
            return;
        }
        
        $created = 0;
        $skipped = 0;
        
        foreach ($seedEntries as $seedEntry) {
            // Check if a consumable already exists for this seed entry
            $existingConsumable = Consumable::where('seed_entry_id', $seedEntry->id)
                ->where('type', 'seed')
                ->first();
            
            if ($existingConsumable) {
                $this->command->comment("Consumable already exists for: {$seedEntry->common_name} ({$seedEntry->cultivar_name})");
                $skipped++;
                continue;
            }
            
            // Create the consumable
            $consumable = Consumable::create([
                'name' => $seedEntry->common_name . ' (' . $seedEntry->cultivar_name . ')',
                'type' => 'seed',
                'seed_entry_id' => $seedEntry->id,
                'supplier_id' => $seedEntry->supplier_id,
                'initial_stock' => 1, // For seeds, we use total_quantity instead
                'consumed_quantity' => 0,
                'total_quantity' => $this->getRandomQuantity($seedEntry),
                'quantity_unit' => $this->getRandomUnit(),
                'quantity_per_unit' => 1,
                'unit' => 'unit',
                'cost_per_unit' => $this->getRandomCost(),
                'restock_threshold' => $this->getRestockThreshold(),
                'restock_quantity' => $this->getRestockQuantity(),
                'lot_no' => $this->generateLotNumber($seedEntry),
                'is_active' => true,
                'notes' => "Auto-generated consumable for {$seedEntry->common_name} - {$seedEntry->cultivar_name}",
            ]);
            
            $this->command->info("Created consumable for: {$seedEntry->common_name} ({$seedEntry->cultivar_name})");
            $created++;
        }
        
        $this->command->info("Seed consumables seeding completed!");
        $this->command->info("Created: {$created} consumables");
        $this->command->info("Skipped: {$skipped} (already existed)");
    }
    
    /**
     * Get a random quantity for the seed based on common seed package sizes
     */
    private function getRandomQuantity(SeedEntry $seedEntry): float
    {
        // Different quantity ranges based on seed type (estimated by common name)
        $smallSeeds = ['arugula', 'amaranth', 'basil', 'chia', 'mustard', 'mizuna'];
        $mediumSeeds = ['broccoli', 'cabbage', 'kale', 'kohlrabi', 'radish', 'turnip'];
        $largeSeeds = ['pea', 'sunflower', 'wheat', 'buckwheat', 'bean'];
        
        $commonName = strtolower($seedEntry->common_name);
        
        if (in_array($commonName, $smallSeeds)) {
            // Small seeds: 100g - 500g
            return rand(100, 500);
        } elseif (in_array($commonName, $largeSeeds)) {
            // Large seeds: 1000g - 5000g
            return rand(1000, 5000);
        } else {
            // Medium seeds or unknown: 250g - 1000g
            return rand(250, 1000);
        }
    }
    
    /**
     * Get a random unit (mostly grams for consistency)
     */
    private function getRandomUnit(): string
    {
        // Use grams as the standard unit for seeds
        return 'g';
    }
    
    /**
     * Get a random cost per unit
     */
    private function getRandomCost(): float
    {
        // Random cost between $0.01 and $0.50 per gram
        return round(rand(1, 50) / 100, 2);
    }
    
    /**
     * Get restock threshold (when to reorder)
     */
    private function getRestockThreshold(): float
    {
        // Restock when down to 100-500g
        return rand(100, 500);
    }
    
    /**
     * Get restock quantity (how much to order)
     */
    private function getRestockQuantity(): float
    {
        // Order 500-2000g when restocking
        return rand(500, 2000);
    }
    
    /**
     * Generate a lot number for the seed
     */
    private function generateLotNumber(SeedEntry $seedEntry): string
    {
        $supplierPrefix = $seedEntry->supplier ? strtoupper(substr($seedEntry->supplier->name, 0, 3)) : 'UNK';
        $year = date('y');
        $randomNumber = rand(1000, 9999);
        
        return "{$supplierPrefix}-{$year}-{$randomNumber}";
    }
}
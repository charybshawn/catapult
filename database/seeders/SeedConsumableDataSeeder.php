<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Consumable;
use Illuminate\Support\Facades\DB;

class SeedConsumableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * This seeder captures the current state of seed consumables in the database
     * and can recreate them exactly as they are now.
     */
    public function run(): void
    {
        $this->command->info('Seeding seed consumables with current database data...');
        
        // Get current seed consumables from the database
        $currentSeedConsumables = Consumable::where('type', 'seed')
            ->where('is_active', true)
            ->get();
        
        if ($currentSeedConsumables->isEmpty()) {
            $this->command->warn('No active seed consumables found in the database.');
            return;
        }
        
        // Store the current data
        $seedData = [];
        foreach ($currentSeedConsumables as $consumable) {
            $seedData[] = [
                'name' => $consumable->name,
                'type' => 'seed',
                'seed_entry_id' => $consumable->seed_entry_id,
                'supplier_id' => $consumable->supplier_id,
                'initial_stock' => $consumable->initial_stock,
                'consumed_quantity' => $consumable->consumed_quantity,
                'total_quantity' => $consumable->total_quantity,
                'quantity_unit' => $consumable->quantity_unit,
                'quantity_per_unit' => $consumable->quantity_per_unit,
                'unit' => $consumable->unit,
                'cost_per_unit' => $consumable->cost_per_unit,
                'restock_threshold' => $consumable->restock_threshold,
                'restock_quantity' => $consumable->restock_quantity,
                'lot_no' => $consumable->lot_no,
                'is_active' => true,
                'notes' => $consumable->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Clear existing seed consumables (optional - comment out if you want to keep existing)
        // DB::table('consumables')->where('type', 'seed')->delete();
        
        // Insert the seed data
        $created = 0;
        foreach ($seedData as $data) {
            // Check if this exact consumable already exists
            $exists = Consumable::where('seed_entry_id', $data['seed_entry_id'])
                ->where('type', 'seed')
                ->where('supplier_id', $data['supplier_id'])
                ->exists();
            
            if (!$exists) {
                Consumable::create($data);
                $created++;
                $this->command->info("Created: {$data['name']} - {$data['total_quantity']}{$data['quantity_unit']} (consumed: {$data['consumed_quantity']}{$data['quantity_unit']})");
            }
        }
        
        $this->command->info("\nSeeding completed! Created {$created} seed consumables.");
        $this->command->info("Total seed consumables in database: " . Consumable::where('type', 'seed')->count());
        
        // Export the current state as a static array for version control
        $this->exportToStaticSeeder($seedData);
    }
    
    /**
     * Export the current data to a static seeder file
     */
    private function exportToStaticSeeder(array $seedData): void
    {
        $className = 'StaticSeedConsumableSeeder';
        $timestamp = date('Y_m_d_His');
        $fileName = database_path("seeders/{$className}_{$timestamp}.php");
        
        $content = "<?php\n\n";
        $content .= "namespace Database\\Seeders;\n\n";
        $content .= "use Illuminate\\Database\\Seeder;\n";
        $content .= "use App\\Models\\Consumable;\n";
        $content .= "use Illuminate\\Support\\Facades\\DB;\n\n";
        $content .= "/**\n";
        $content .= " * Static seed consumable data captured on " . date('Y-m-d H:i:s') . "\n";
        $content .= " * Total records: " . count($seedData) . "\n";
        $content .= " */\n";
        $content .= "class {$className}_{$timestamp} extends Seeder\n";
        $content .= "{\n";
        $content .= "    public function run(): void\n";
        $content .= "    {\n";
        $content .= "        \$seedConsumables = " . var_export($seedData, true) . ";\n\n";
        $content .= "        foreach (\$seedConsumables as \$data) {\n";
        $content .= "            Consumable::updateOrCreate(\n";
        $content .= "                [\n";
        $content .= "                    'seed_entry_id' => \$data['seed_entry_id'],\n";
        $content .= "                    'type' => 'seed',\n";
        $content .= "                    'supplier_id' => \$data['supplier_id'],\n";
        $content .= "                ],\n";
        $content .= "                \$data\n";
        $content .= "            );\n";
        $content .= "        }\n";
        $content .= "    }\n";
        $content .= "}\n";
        
        file_put_contents($fileName, $content);
        $this->command->info("Static seeder exported to: {$fileName}");
    }
}
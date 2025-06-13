<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Consumable;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all seed consumables that have a master_seed_catalog_id but no master_cultivar_id
        $consumables = Consumable::where('type', 'seed')
            ->whereNotNull('master_seed_catalog_id')
            ->whereNull('master_cultivar_id')
            ->with('masterSeedCatalog')
            ->get();

        foreach ($consumables as $consumable) {
            $masterSeedCatalog = $consumable->masterSeedCatalog;
            
            if (!$masterSeedCatalog) {
                continue;
            }

            // Try to match the consumable name to a cultivar
            // The consumable name might contain the cultivar in parentheses
            $consumableName = $consumable->name;
            
            // Extract cultivar name from consumable name if it's in parentheses
            $cultivarFromName = null;
            if (preg_match('/\(([^)]+)\)/', $consumableName, $matches)) {
                $cultivarFromName = $matches[1];
            }

            // Find or create the master cultivar
            $masterCultivar = null;

            if ($cultivarFromName) {
                // First try to find existing cultivar by exact match
                $masterCultivar = MasterCultivar::where('master_seed_catalog_id', $masterSeedCatalog->id)
                    ->where('cultivar_name', $cultivarFromName)
                    ->first();

                // If not found, check aliases
                if (!$masterCultivar) {
                    $masterCultivar = MasterCultivar::where('master_seed_catalog_id', $masterSeedCatalog->id)
                        ->whereJsonContains('aliases', $cultivarFromName)
                        ->first();
                }
            }

            // If still not found and the master seed catalog has cultivars in JSON
            if (!$masterCultivar && !empty($masterSeedCatalog->cultivars)) {
                $cultivars = is_array($masterSeedCatalog->cultivars) 
                    ? $masterSeedCatalog->cultivars 
                    : json_decode($masterSeedCatalog->cultivars, true);

                // If only one cultivar exists, use it
                if (count($cultivars) === 1) {
                    $cultivarName = $cultivars[0];
                    
                    // Find or create the cultivar
                    $masterCultivar = MasterCultivar::firstOrCreate(
                        [
                            'master_seed_catalog_id' => $masterSeedCatalog->id,
                            'cultivar_name' => $cultivarName,
                        ],
                        [
                            'is_active' => true,
                            'description' => 'Auto-created from seed consumable migration',
                        ]
                    );
                } elseif ($cultivarFromName && in_array($cultivarFromName, $cultivars)) {
                    // If we found a cultivar name in the consumable name and it matches one in the catalog
                    $masterCultivar = MasterCultivar::firstOrCreate(
                        [
                            'master_seed_catalog_id' => $masterSeedCatalog->id,
                            'cultivar_name' => $cultivarFromName,
                        ],
                        [
                            'is_active' => true,
                            'description' => 'Auto-created from seed consumable migration',
                        ]
                    );
                }
            }

            // Update the consumable with the master_cultivar_id if found
            if ($masterCultivar) {
                $consumable->update(['master_cultivar_id' => $masterCultivar->id]);
                
                echo "Updated consumable '{$consumable->name}' with cultivar '{$masterCultivar->cultivar_name}'\n";
            } else {
                echo "Could not determine cultivar for consumable '{$consumable->name}' (Catalog: {$masterSeedCatalog->common_name})\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all master_cultivar_id back to null
        Consumable::where('type', 'seed')
            ->whereNotNull('master_cultivar_id')
            ->update(['master_cultivar_id' => null]);
    }
};
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\Consumable;
use App\Models\MasterSeedCatalog;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;

echo "Starting to fix consumable relationships...\n";

// Get all seed consumables with missing relationships
$seedConsumables = Consumable::with('consumableType')
    ->whereHas('consumableType', function($q) { 
        $q->where('code', 'seed'); 
    })
    ->whereNull('supplier_id')
    ->orWhereNull('master_seed_catalog_id')
    ->get();

echo "Found " . $seedConsumables->count() . " seed consumables with missing relationships\n";

// Default supplier for seeds (you can modify this)
$defaultSeedSupplier = Supplier::where('name', 'Mumm\'s Sprouting Seeds')->first();
if (!$defaultSeedSupplier) {
    $defaultSeedSupplier = Supplier::first(); // Fallback to first supplier
}

echo "Using default supplier: " . $defaultSeedSupplier->name . " (ID: " . $defaultSeedSupplier->id . ")\n";

$updated = 0;
$skipped = 0;

foreach ($seedConsumables as $consumable) {
    echo "\nProcessing: " . $consumable->name . " (ID: " . $consumable->id . ")\n";
    
    // Parse the name to extract common name and cultivar
    // Format is typically: "Common Name (Cultivar)"
    if (preg_match('/^(.+?)\s*\((.+?)\)$/', $consumable->name, $matches)) {
        $commonName = trim($matches[1]);
        $cultivar = trim($matches[2]);
        
        echo "  Parsed - Common: '$commonName', Cultivar: '$cultivar'\n";
        
        // Find matching master seed catalog
        $masterCatalog = MasterSeedCatalog::where('common_name', 'ILIKE', $commonName)->first();
        
        if ($masterCatalog) {
            echo "  Found master catalog: " . $masterCatalog->common_name . " (ID: " . $masterCatalog->id . ")\n";
            
            // Check if the cultivar exists in the catalog
            $cultivars = is_array($masterCatalog->cultivars) ? $masterCatalog->cultivars : [];
            $cultivarIndex = null;
            
            foreach ($cultivars as $index => $catalogCultivar) {
                if (strcasecmp(trim($catalogCultivar), $cultivar) === 0) {
                    $cultivarIndex = $index;
                    break;
                }
            }
            
            if ($cultivarIndex !== null) {
                echo "  Found matching cultivar at index: $cultivarIndex\n";
                
                // Update the consumable
                $consumable->update([
                    'supplier_id' => $defaultSeedSupplier->id,
                    'master_seed_catalog_id' => $masterCatalog->id . ':' . $cultivarIndex,
                    'cultivar' => $cultivar
                ]);
                
                echo "  ✓ Updated successfully\n";
                $updated++;
            } else {
                echo "  ✗ Cultivar '$cultivar' not found in catalog. Available: " . json_encode($cultivars) . "\n";
                
                // Still update with supplier and catalog, but without cultivar match
                $consumable->update([
                    'supplier_id' => $defaultSeedSupplier->id,
                    'master_seed_catalog_id' => $masterCatalog->id . ':0', // Use first cultivar as default
                    'cultivar' => $cultivar
                ]);
                
                echo "  ⚠ Updated with default cultivar\n";
                $updated++;
            }
        } else {
            echo "  ✗ No master catalog found for '$commonName'\n";
            
            // Still update with supplier
            $consumable->update([
                'supplier_id' => $defaultSeedSupplier->id,
                'cultivar' => $cultivar
            ]);
            
            echo "  ⚠ Updated with supplier only\n";
            $updated++;
        }
    } else {
        echo "  ✗ Could not parse name format: " . $consumable->name . "\n";
        
        // Still update with supplier
        $consumable->update([
            'supplier_id' => $defaultSeedSupplier->id
        ]);
        
        echo "  ⚠ Updated with supplier only\n";
        $updated++;
    }
}

echo "\n=== Summary ===\n";
echo "Updated: $updated\n";
echo "Skipped: $skipped\n";
echo "Done!\n";
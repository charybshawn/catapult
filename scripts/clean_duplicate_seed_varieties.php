<?php

/**
 * Seed Variety Duplicate Cleanup Script
 * 
 * This script identifies duplicate seed varieties by name, keeps the oldest record,
 * and updates all references in recipes and consumables to point to the oldest record.
 * 
 * Usage: php scripts/clean_duplicate_seed_varieties.php
 */

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SeedVariety;
use App\Models\Recipe;
use App\Models\Consumable;
use Illuminate\Support\Facades\DB;

echo "Starting cleanup of duplicate seed varieties...\n";

// Get all seed variety names with duplicates
$duplicateNames = SeedVariety::select('name')
    ->groupBy('name')
    ->havingRaw('COUNT(*) > 1')
    ->get()
    ->pluck('name')
    ->toArray();

echo "Found " . count($duplicateNames) . " seed varieties with duplicate names.\n";

// Process each duplicate set
foreach ($duplicateNames as $name) {
    echo "\nProcessing duplicates for: $name\n";
    
    // Get all duplicates, ordered by ID (oldest first)
    $duplicates = SeedVariety::where('name', $name)
        ->orderBy('id', 'asc')
        ->get();
    
    // Keep the oldest record
    $primaryId = $duplicates->first()->id;
    
    echo "  Primary record ID: $primaryId\n";
    
    // Update all references to the duplicate IDs
    $duplicateIds = $duplicates->pluck('id')->filter(function ($id) use ($primaryId) {
        return $id != $primaryId;
    })->toArray();
    
    echo "  Duplicate IDs to redirect: " . implode(', ', $duplicateIds) . "\n";
    
    // Start a database transaction to ensure all updates complete together
    DB::beginTransaction();
    
    try {
        // Update recipes
        $recipeCount = Recipe::whereIn('seed_variety_id', $duplicateIds)
            ->update(['seed_variety_id' => $primaryId]);
        
        echo "  Updated $recipeCount recipes\n";
        
        // Update consumables
        $consumableCount = Consumable::whereIn('seed_variety_id', $duplicateIds)
            ->update(['seed_variety_id' => $primaryId]);
        
        echo "  Updated $consumableCount consumables\n";
        
        // Delete the duplicate records
        SeedVariety::whereIn('id', $duplicateIds)->delete();
        
        echo "  Deleted " . count($duplicateIds) . " duplicate records\n";
        
        // Commit the transaction
        DB::commit();
        echo "  Successfully cleaned up duplicates for: $name\n";
    } catch (\Exception $e) {
        // Roll back the transaction if something goes wrong
        DB::rollBack();
        echo "  ERROR: Failed to clean up duplicates for: $name\n";
        echo "  " . $e->getMessage() . "\n";
    }
}

echo "\nSeed variety cleanup complete!\n"; 
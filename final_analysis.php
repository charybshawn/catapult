<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Product;
use App\Models\Recipe;
use App\Models\MasterSeedCatalog;

echo "=== FINAL ANALYSIS: Why Basil and Cilantro don't generate crop plans ===\n\n";

// Check Basil
echo "BASIL ANALYSIS:\n";
$basilProduct = Product::where('name', 'like', '%basil%')->first();
if ($basilProduct) {
    echo "Product: {$basilProduct->name} (ID: {$basilProduct->id})\n";
    echo "Master Seed Catalog ID: {$basilProduct->master_seed_catalog_id}\n";
    
    if ($basilProduct->master_seed_catalog_id) {
        $basilCatalog = MasterSeedCatalog::find($basilProduct->master_seed_catalog_id);
        if ($basilCatalog) {
            echo "Master Seed Catalog common_name: '{$basilCatalog->common_name}'\n";
            echo "Master Seed Catalog cultivar_name: " . ($basilCatalog->cultivar_name ?: 'NULL') . "\n";
        }
    }
}

$basilRecipe = Recipe::where('name', 'like', '%basil%')->first();
if ($basilRecipe) {
    echo "\nRecipe: {$basilRecipe->name} (ID: {$basilRecipe->id})\n";
    echo "Recipe master_seed_catalog_id: {$basilRecipe->master_seed_catalog_id}\n";
    echo "Recipe is_active: " . ($basilRecipe->is_active ? 'Yes' : 'No') . "\n";
    echo "Recipe lot_depleted_at: " . ($basilRecipe->lot_depleted_at ?: 'NULL') . "\n";
    
    // Check if recipe has common_name field
    $recipeAttributes = $basilRecipe->getAttributes();
    echo "Recipe has 'common_name' field: " . (isset($recipeAttributes['common_name']) ? 'Yes' : 'No') . "\n";
    echo "Recipe has 'cultivar_name' field: " . (isset($recipeAttributes['cultivar_name']) ? 'Yes' : 'No') . "\n";
}

echo "\n" . str_repeat('-', 50) . "\n\n";

// Check Cilantro
echo "CILANTRO ANALYSIS:\n";
$cilantroProduct = Product::where('name', 'like', '%cilantro%')->first();
if ($cilantroProduct) {
    echo "Product: {$cilantroProduct->name} (ID: {$cilantroProduct->id})\n";
    echo "Master Seed Catalog ID: {$cilantroProduct->master_seed_catalog_id}\n";
    
    if ($cilantroProduct->master_seed_catalog_id) {
        $cilantroCatalog = MasterSeedCatalog::find($cilantroProduct->master_seed_catalog_id);
        if ($cilantroCatalog) {
            echo "Master Seed Catalog common_name: '{$cilantroCatalog->common_name}'\n";
            echo "Master Seed Catalog cultivar_name: " . ($cilantroCatalog->cultivar_name ?: 'NULL') . "\n";
        }
    }
}

$cilantroRecipe = Recipe::where('name', 'like', '%cilantro%')->first();
if ($cilantroRecipe) {
    echo "\nRecipe: {$cilantroRecipe->name} (ID: {$cilantroRecipe->id})\n";
} else {
    echo "\nNO CILANTRO RECIPE FOUND\n";
}

echo "\n" . str_repeat('-', 50) . "\n\n";

// Check Recipe table structure
echo "RECIPE TABLE STRUCTURE:\n";
$recipeColumns = \DB::getSchemaBuilder()->getColumnListing('recipes');
echo "Recipe table columns: " . implode(', ', $recipeColumns) . "\n";

echo "\n" . str_repeat('-', 50) . "\n\n";

echo "PROBLEM IDENTIFIED:\n";
echo "1. The CropPlanningService looks for recipes with 'common_name' and 'cultivar_name' fields\n";
echo "2. The Recipe model doesn't have these fields - it only has 'name' and 'master_seed_catalog_id'\n";
echo "3. This is why the findActiveRecipeForVariety() method can't find any recipes\n";
echo "4. The system needs to be updated to match recipes by master_seed_catalog_id instead\n";

// Clean up
unlink(__FILE__);
<?php

// Auto-load composer dependencies
require_once __DIR__ . '/vendor/autoload.php';

// Boot the application
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Query for all seed consumables
$seeds = \App\Models\Consumable::where('type', 'seed')->get();

echo "Found " . count($seeds) . " seeds\n\n";

// Display info about each seed
foreach ($seeds as $seed) {
    echo "Seed ID: " . $seed->id . "\n";
    echo "Name: " . $seed->name . "\n";
    echo "Quantity Unit: " . ($seed->quantity_unit ?: 'null') . "\n";
    echo "Unit: " . ($seed->unit ?: 'null') . "\n";
    echo "Initial Stock: " . $seed->initial_stock . "\n";
    echo "Consumed Quantity: " . $seed->consumed_quantity . "\n";
    echo "Current Stock: " . $seed->current_stock . "\n";
    echo "Quantity Per Unit: " . ($seed->quantity_per_unit ?: 'null') . "\n";
    
    // Check if this is the Speckled P seed
    if (stripos($seed->name, 'Speckled') !== false) {
        echo "*** THIS IS SPECKLED P ***\n";
    }
    
    echo "\n----------------------------\n\n";
} 
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\Crop;
use App\Models\Activity;

class TestRelationshipLogging extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:test-relationships';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test activity logging with relationships';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing relationship logging in activity logs...');

        // Test with a Product
        $product = Product::with(['category', 'priceVariations'])->first();
        if ($product) {
            $this->info("\nTesting Product relationship logging...");
            $originalDescription = $product->description;
            $product->update(['description' => 'Testing relationship logging at ' . now()]);
            
            $activity = Activity::where('subject_type', Product::class)
                ->where('subject_id', $product->id)
                ->latest()
                ->first();
                
            if ($activity) {
                $this->info("Activity logged for Product #{$product->id}");
                $this->line("Properties: " . json_encode($activity->properties, JSON_PRETTY_PRINT));
            }
            
            // Restore original value
            $product->update(['description' => $originalDescription]);
        }

        // Test with an Order
        $order = Order::with(['customer', 'status', 'orderType'])->first();
        if ($order) {
            $this->info("\nTesting Order relationship logging...");
            $originalNotes = $order->notes;
            $order->update(['notes' => 'Testing relationship logging at ' . now()]);
            
            $activity = Activity::where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->latest()
                ->first();
                
            if ($activity) {
                $this->info("Activity logged for Order #{$order->id}");
                $this->line("Properties: " . json_encode($activity->properties, JSON_PRETTY_PRINT));
            }
            
            // Restore original value
            $order->update(['notes' => $originalNotes]);
        }

        // Test with a Recipe
        $recipe = Recipe::with(['seedEntry', 'soilConsumable'])->first();
        if ($recipe) {
            $this->info("\nTesting Recipe relationship logging...");
            $recipe->update(['notes' => 'Testing relationship logging at ' . now()]);
            
            $activity = Activity::where('subject_type', Recipe::class)
                ->where('subject_id', $recipe->id)
                ->latest()
                ->first();
                
            if ($activity) {
                $this->info("Activity logged for Recipe #{$recipe->id}");
                $this->line("Properties: " . json_encode($activity->properties, JSON_PRETTY_PRINT));
            }
        }

        $this->info("\nRelationship logging test completed!");
    }
}
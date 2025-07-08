<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Services\OrderPlanningService;

echo "=== ORDER-DRIVEN CROP PLANNING WORKFLOW EXAMPLE ===\n\n";

// Scenario: Restaurant orders microgreens for next week (too soon!)
$customer = Customer::first();
$nextWeek = now()->addDays(7);

echo "📅 Today's Date: " . now()->format('M j, Y') . "\n\n";

echo "1. CUSTOMER PLACES ORDER\n";
echo "   Customer: {$customer->business_name}\n";
echo "   Requested Delivery: {$nextWeek->format('M j, Y')} (7 days from now)\n";

// Create order
$order = Order::create([
    'user_id' => 1,
    'customer_id' => $customer->id,
    'harvest_date' => $nextWeek->copy()->subDay(),
    'delivery_date' => $nextWeek,
    'order_status_id' => 1, // New
    'unified_status_id' => 1,
]);

// Add items
$items = [
    ['name' => 'Basil', 'quantity' => 500],
    ['name' => 'Radish', 'quantity' => 300],
    ['name' => 'Rainbow Mix', 'quantity' => 200],
];

echo "   Order Items:\n";
foreach ($items as $item) {
    $product = Product::where('name', 'like', "%{$item['name']}%")->first();
    if ($product) {
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => $item['quantity'],
            'price' => 20.00,
        ]);
        echo "     - {$item['quantity']}g {$product->name}\n";
    }
}

echo "\n2. AUTOMATIC CROP PLAN GENERATION (via OrderObserver)\n";

$service = app(OrderPlanningService::class);
$result = $service->generatePlansForOrder($order);

if (!$result['success'] && !empty($result['issues'])) {
    echo "   ❌ Cannot fulfill order by requested date!\n\n";
    echo "   Timing Issues:\n";
    foreach ($result['issues'] as $issue) {
        if (is_array($issue)) {
            if (isset($issue['recipe'])) {
                $variety = explode(' - ', $issue['recipe'])[0];
                echo "   - {$variety}: {$issue['issue']}\n";
                if (isset($issue['plant_date'])) {
                    echo "     Plant date needed: {$issue['plant_date']}\n";
                }
                if (isset($issue['days_overdue'])) {
                    echo "     (That's " . round($issue['days_overdue']) . " days ago!)\n";
                } elseif (isset($issue['hours_until_planting']) && $issue['hours_until_planting'] > 0) {
                    $days = round($issue['hours_until_planting'] / 24, 1);
                    echo "     (Only $days days until planting - too tight!)\n";
                }
            } else {
                // Simple string issue
                echo "   - $issue\n";
            }
        } else {
            echo "   - $issue\n";
        }
    }
    
    echo "\n3. SYSTEM ALERTS\n";
    echo "   📧 Email sent to manager\n";
    echo "   🔔 Dashboard notification created\n";
    echo "   📱 SMS alert (if configured)\n";
    
    echo "\n4. MANAGER REVIEWS OPTIONS\n";
    echo "   Option A: Negotiate later delivery date\n";
    echo "   Option B: Check existing inventory\n";
    echo "   Option C: Expedite with special growing methods\n";
    
    echo "\n5. RESOLUTION: Manager calls customer\n";
    echo "   'Hi, this is the farm manager. Due to growing times,\n";
    echo "    the earliest we can deliver your order is...'\n";
    
    // Calculate realistic delivery date
    $realisticDate = now()->addDays(25); // Basil needs 21 days + buffer
    echo "\n   Agreed new delivery date: {$realisticDate->format('M j, Y')}\n";
    
    // Update order
    $order->delivery_date = $realisticDate;
    $order->harvest_date = $realisticDate->copy()->subDay();
    $order->save();
    
    echo "\n6. REGENERATE CROP PLANS WITH NEW DATE\n";
    $result = $service->generatePlansForOrder($order);
}

if ($result['success']) {
    echo "   ✅ Successfully generated {$result['plans']->count()} crop plans!\n\n";
    
    $aggregationService = app(\App\Services\CropPlanAggregationService::class);
    
    foreach ($result['plans'] as $plan) {
        $plan->refresh(); // Reload relationships
        echo "   📋 Crop Plan #{$plan->id}:\n";
        echo "      Variety: " . ($plan->variety ? $plan->variety->full_name : $plan->recipe->common_name) . "\n";
        echo "      Recipe: {$plan->recipe->name}\n";
        echo "      Amount: {$plan->grams_needed}g ({$plan->trays_needed} trays)\n";
        
        if ($plan->seed_soak_date) {
            echo "      💧 Seed Soak: {$plan->seed_soak_date->format('M j')} ";
            echo "(" . now()->diffInDays($plan->seed_soak_date) . " days from now)\n";
        }
        
        echo "      🌱 Plant Date: {$plan->plant_by_date->format('M j')} ";
        echo "(" . now()->diffInDays($plan->plant_by_date) . " days from now)\n";
        
        echo "      ✂️ Harvest: {$plan->expected_harvest_date->format('M j')}\n";
        echo "      📦 Delivery: {$plan->delivery_date->format('M j')}\n\n";
    }
    
    echo "7. WHAT HAPPENS NEXT\n";
    echo "   📅 Plans appear on calendar for growers\n";
    echo "   ✅ Manager reviews and approves plans\n";
    echo "   🔔 Daily reminders: 'Tomorrow: Plant 5 trays Basil'\n";
    echo "   👨‍🌾 Growers plant crops and update system\n";
    echo "   📊 Dashboard tracks progress\n";
    
    echo "\n8. AGGREGATION EXAMPLE\n";
    echo "   If another order comes in for the same delivery date:\n";
    echo "   - System finds existing plans for that harvest date\n";
    echo "   - Aggregates quantities (e.g., 5 + 3 = 8 trays total)\n";
    echo "   - Updates calendar to show combined requirements\n";
}

// Cleanup
echo "\n\nCleaning up demo data...\n";
if (isset($order)) {
    $order->cropPlans()->delete();
    $order->orderItems()->delete();
    $order->delete();
}

echo "Demo complete! 🌱\n";
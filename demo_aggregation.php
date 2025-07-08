<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\CropPlan;
use App\Services\OrderPlanningService;
use App\Services\CropPlanAggregationService;

echo "=== CROP PLAN AGGREGATION DEMO ===\n\n";

$customer1 = Customer::find(1);
$customer2 = Customer::find(2) ?: Customer::create([
    'contact_name' => 'Jane Smith',
    'business_name' => 'Green Cafe',
    'email' => 'jane@greencafe.com',
    'customer_type_id' => 1,
]);

$deliveryDate = now()->addDays(30);
$basilProduct = Product::where('name', 'like', '%Basil%')->first();

echo "📅 Delivery Date: {$deliveryDate->format('M j, Y')}\n\n";

// Create first order
echo "1. FIRST ORDER - {$customer1->business_name}\n";
$order1 = Order::create([
    'user_id' => 1,
    'customer_id' => $customer1->id,
    'harvest_date' => $deliveryDate->copy()->subDay(),
    'delivery_date' => $deliveryDate,
    'order_status_id' => 1,
    'unified_status_id' => 1,
]);

OrderItem::create([
    'order_id' => $order1->id,
    'product_id' => $basilProduct->id,
    'quantity' => 500,
    'price' => 25.00,
]);
echo "   Ordered: 500g Basil\n";

$service = app(OrderPlanningService::class);
$result1 = $service->generatePlansForOrder($order1);

if ($result1['success']) {
    echo "   ✅ Generated {$result1['plans']->count()} crop plan(s)\n";
    foreach ($result1['plans'] as $plan) {
        echo "      Plan #{$plan->id}: {$plan->trays_needed} trays of {$plan->recipe->common_name}\n";
    }
}

// Create second order for same delivery date
echo "\n2. SECOND ORDER - {$customer2->business_name} (same delivery date)\n";
$order2 = Order::create([
    'user_id' => 1,
    'customer_id' => $customer2->id,
    'harvest_date' => $deliveryDate->copy()->subDay(),
    'delivery_date' => $deliveryDate,
    'order_status_id' => 1,
    'unified_status_id' => 1,
]);

OrderItem::create([
    'order_id' => $order2->id,
    'product_id' => $basilProduct->id,
    'quantity' => 300,
    'price' => 15.00,
]);
echo "   Ordered: 300g Basil\n";

echo "\n3. AGGREGATION IN ACTION\n";
$result2 = $service->generatePlansForOrder($order2);

if ($result2['success']) {
    echo "   ✅ Plan generation complete\n";
    
    // Show what happened
    $allBasilPlans = CropPlan::whereHas('recipe', function($q) {
        $q->where('common_name', 'Basil');
    })->whereDate('expected_harvest_date', $deliveryDate->copy()->subDay())
      ->with('status')
      ->get();
    
    echo "\n   Current Basil plans for this harvest date:\n";
    foreach ($allBasilPlans as $plan) {
        echo "      Plan #{$plan->id}: ";
        echo "{$plan->trays_needed} trays, ";
        echo "{$plan->grams_needed}g, ";
        echo "Status: {$plan->status->name}\n";
        
        if ($plan->status->code === 'cancelled') {
            echo "        (Aggregated into another plan)\n";
        }
        
        if ($plan->calculation_details && isset($plan->calculation_details['aggregation_history'])) {
            echo "        Aggregation history:\n";
            foreach ($plan->calculation_details['aggregation_history'] as $history) {
                echo "          - Added {$history['trays_added']} trays from Order #{$history['order_id']}\n";
            }
        }
    }
}

// Show the aggregated result
echo "\n4. FINAL RESULT\n";
$activePlans = CropPlan::whereHas('status', function($q) {
    $q->where('code', '!=', 'cancelled');
})->whereDate('expected_harvest_date', $deliveryDate->copy()->subDay())
  ->get();

echo "   Active plans for {$deliveryDate->copy()->subDay()->format('M j')} harvest:\n";
foreach ($activePlans as $plan) {
    echo "   📋 Plan #{$plan->id}:\n";
    echo "      Recipe: {$plan->recipe->name}\n";
    echo "      Total quantity: {$plan->grams_needed}g\n";
    echo "      Total trays: {$plan->trays_needed}\n";
    echo "      Plant by: {$plan->plant_by_date->format('M j, Y')}\n";
    
    if ($plan->admin_notes) {
        echo "      Notes: {$plan->admin_notes}\n";
    }
}

echo "\n5. BENEFITS OF AGGREGATION\n";
echo "   ✅ Single planting task instead of multiple\n";
echo "   ✅ Efficient use of growing space\n";
echo "   ✅ Easier for growers to manage\n";
echo "   ✅ Better resource planning\n";
echo "   ✅ Reduced labor costs\n";

// Cleanup
echo "\n\nCleaning up demo data...\n";
CropPlan::whereIn('order_id', [$order1->id, $order2->id])->delete();
$order1->orderItems()->delete();
$order2->orderItems()->delete();
$order1->delete();
$order2->delete();
if ($customer2->id !== 1 && $customer2->id !== 2) {
    $customer2->delete();
}

echo "Demo complete! 🌱\n";
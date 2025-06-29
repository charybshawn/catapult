<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use App\Services\RecurringOrderService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class RecurringOrderGenerationTest extends TestCase
{
    use RefreshDatabase;

    private RecurringOrderService $recurringOrderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->recurringOrderService = app(RecurringOrderService::class);
    }

    /** @test */
    public function it_generates_next_recurring_order_correctly()
    {
        $user = User::factory()->create(['customer_type' => 'retail']);
        $product = Product::factory()->create();
        $priceVariation = PriceVariation::factory()->forProduct($product)->default()->create();
        
        $template = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'recurring_frequency' => 'weekly',
            'recurring_start_date' => now(),
            'last_generated_at' => now(),
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $template->id,
            'product_id' => $product->id,
            'price_variation_id' => $priceVariation->id,
            'quantity' => 5,
            'price' => 10.00,
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        $this->assertNotNull($newOrder);
        $this->assertEquals($template->id, $newOrder->parent_recurring_order_id);
        $this->assertEquals($user->id, $newOrder->user_id);
        $this->assertEquals('pending', $newOrder->status);
        $this->assertFalse($newOrder->is_recurring);
        
        // Check that order items were copied
        $this->assertCount(1, $newOrder->orderItems);
        $newOrderItem = $newOrder->orderItems->first();
        $this->assertEquals($product->id, $newOrderItem->product_id);
        $this->assertEquals($priceVariation->id, $newOrderItem->price_variation_id);
        $this->assertEquals(5, $newOrderItem->quantity);
    }

    /** @test */
    public function it_recalculates_prices_when_generating_recurring_order()
    {
        $wholesaleUser = User::factory()->create(['customer_type' => 'wholesale']);
        $product = Product::factory()->create([
            'base_price' => 10.00,
            'wholesale_price' => 8.00,
        ]);
        $priceVariation = PriceVariation::factory()->forProduct($product)->create(['price' => 10.00]);
        
        $template = Order::factory()->recurring()->create([
            'user_id' => $wholesaleUser->id,
            'customer_type' => 'wholesale',
        ]);

        OrderItem::factory()->create([
            'order_id' => $template->id,
            'product_id' => $product->id,
            'price_variation_id' => $priceVariation->id,
            'quantity' => 3,
            'price' => 10.00, // Original retail price
        ]);

        // The product method should work correctly with our fixes

        $newOrder = $template->generateNextRecurringOrder();
        $newOrderItem = $newOrder->orderItems->first();

        // Price should be recalculated for wholesale customer
        $this->assertNotEquals(10.00, $newOrderItem->price);
    }

    /** @test */
    public function it_sets_correct_dates_for_generated_order()
    {
        $startDate = Carbon::parse('2025-01-01');
        Carbon::setTestNow($startDate);

        $template = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'recurring_start_date' => $startDate,
            'last_generated_at' => $startDate,
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        $expectedHarvestDate = $startDate->copy()->addWeek();
        $expectedDeliveryDate = $expectedHarvestDate->copy()->addDay();

        $this->assertEquals($expectedHarvestDate->format('Y-m-d'), $newOrder->harvest_date->format('Y-m-d'));
        $this->assertEquals($expectedDeliveryDate->format('Y-m-d'), $newOrder->delivery_date->format('Y-m-d'));

        Carbon::setTestNow();
    }

    /** @test */
    public function it_prevents_duplicate_orders_for_same_delivery_date()
    {
        $template = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'last_generated_at' => now(),
        ]);

        $deliveryDate = now()->addWeek()->addDay();
        
        // Create an existing order for the same delivery date
        Order::factory()->generatedFromRecurring($template)->create([
            'delivery_date' => $deliveryDate,
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        $this->assertNull($newOrder); // Should not create duplicate
    }

    /** @test */
    public function it_deactivates_recurring_order_past_end_date()
    {
        $template = Order::factory()->recurring()->create([
            'recurring_end_date' => now()->subDays(1), // Past end date
            'is_recurring_active' => true,
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        $this->assertNull($newOrder);
        $this->assertFalse($template->fresh()->is_recurring_active);
    }

    /** @test */
    public function it_does_not_generate_order_for_inactive_template()
    {
        $template = Order::factory()->recurring()->create([
            'is_recurring_active' => false,
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        $this->assertNull($newOrder);
    }

    /** @test */
    public function it_preserves_b2b_order_properties()
    {
        $template = Order::factory()->b2bRecurring()->create([
            'order_type' => 'b2b',
            'billing_frequency' => 'monthly',
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        $this->assertNotNull($newOrder);
        $this->assertEquals('b2b', $newOrder->order_type);
        $this->assertEquals('monthly', $newOrder->billing_frequency);
        $this->assertFalse($newOrder->is_recurring);
        $this->assertEquals('pending', $newOrder->status);
    }

    /** @test */
    public function it_copies_packaging_types_to_generated_order()
    {
        $template = Order::factory()->recurring()->create([
            'recurring_start_date' => now()->subDays(7),
            'last_generated_at' => now()->subDays(7),
        ]);
        
        // Create actual PackagingType record
        $packagingType = \App\Models\PackagingType::create([
            'name' => 'Test Container',
            'display_name' => 'Test Container',
            'capacity_volume' => 16.0,
            'cost_per_unit' => 0.50,
            'is_active' => true,
        ]);
        
        // Add packaging to template with proper foreign key
        $template->packagingTypes()->attach($packagingType->id, [
            'quantity' => 5,
            'notes' => 'Test packaging',
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        // Check that packaging was copied
        $this->assertNotNull($newOrder);
        $this->assertCount($template->packagingTypes->count(), $newOrder->packagingTypes);
        
        $copiedPackaging = $newOrder->packagingTypes->first();
        $this->assertEquals($packagingType->id, $copiedPackaging->id);
        $this->assertEquals(5, $copiedPackaging->pivot->quantity);
        $this->assertEquals('Test packaging', $copiedPackaging->pivot->notes);
    }

    /** @test */
    public function it_updates_template_generation_dates_after_creating_order()
    {
        $initialDate = now();
        Carbon::setTestNow($initialDate);

        $template = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'last_generated_at' => $initialDate,
        ]);

        $oldNextGenDate = $template->next_generation_date;

        $newOrder = $template->generateNextRecurringOrder();

        $template->refresh();

        $this->assertNotNull($newOrder);
        $this->assertNotNull($template->last_generated_at);
        $this->assertNotEquals($oldNextGenDate, $template->next_generation_date);

        Carbon::setTestNow();
    }

    /** @test */
    public function recurring_order_service_processes_all_active_templates()
    {
        // Create multiple recurring templates
        $activeTemplate1 = Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(), // Due for generation
        ]);
        
        $activeTemplate2 = Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(), // Due for generation
        ]);
        
        $inactiveTemplate = Order::factory()->recurring()->create([
            'is_recurring_active' => false,
        ]);
        
        $futureTemplate = Order::factory()->recurring()->create([
            'is_recurring_active' => true,
            'next_generation_date' => now()->addDays(1), // Not due yet
        ]);

        // Add order items to active templates
        OrderItem::factory()->create(['order_id' => $activeTemplate1->id]);
        OrderItem::factory()->create(['order_id' => $activeTemplate2->id]);

        $results = $this->recurringOrderService->processRecurringOrders();

        $this->assertEquals(4, $results['processed']); // All templates checked
        $this->assertEquals(2, $results['generated']); // Only 2 generated
        $this->assertEmpty($results['errors']);
    }

    /** @test */
    public function recurring_order_service_handles_generation_errors_gracefully()
    {
        $template = Order::factory()->recurring()->create([
            'user_id' => 999999, // Non-existent user to cause error
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(),
        ]);

        OrderItem::factory()->create(['order_id' => $template->id]);

        $results = $this->recurringOrderService->processRecurringOrders();

        $this->assertEquals(1, $results['processed']);
        $this->assertEquals(0, $results['generated']);
        $this->assertCount(1, $results['errors']);
        $this->assertEquals($template->id, $results['errors'][0]['order_id']);
    }

    /** @test */
    public function it_gets_upcoming_recurring_orders_correctly()
    {
        $today = now();
        
        // Orders due today
        Order::factory()->recurring()->create([
            'next_generation_date' => $today,
        ]);
        
        // Orders due tomorrow
        Order::factory()->recurring()->create([
            'next_generation_date' => $today->copy()->addDay(),
        ]);
        
        // Orders due next week (outside 7-day window)
        Order::factory()->recurring()->create([
            'next_generation_date' => $today->copy()->addDays(8),
        ]);

        $upcomingOrders = $this->recurringOrderService->getUpcomingRecurringOrders(7);

        $this->assertCount(2, $upcomingOrders);
    }

    /**
     * Mock product pricing method for testing price recalculation
     */
    private function mockProductPricing($product, $user, $priceVariation, $expectedPrice)
    {
        // In a real test, you might use Mockery or similar mocking framework
        // For now, we'll assume the method exists and works correctly
        // This is a placeholder for the actual implementation
    }
}
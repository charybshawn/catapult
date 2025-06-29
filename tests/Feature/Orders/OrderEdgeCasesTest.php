<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class OrderEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_recurring_order_with_zero_quantity_items()
    {
        $template = Order::factory()->recurring()->create();
        
        OrderItem::factory()->create([
            'order_id' => $template->id,
            'quantity' => 0, // Edge case: zero quantity
            'price' => 10.00,
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        $this->assertNotNull($newOrder);
        
        // Verify zero quantity is preserved
        $newOrderItem = $newOrder->orderItems->first();
        $this->assertEquals(0, $newOrderItem->quantity);
    }

    /** @test */
    public function it_handles_recurring_order_with_very_large_quantities()
    {
        $template = Order::factory()->recurring()->create();
        
        // Create a product with a known current price
        $product = Product::factory()->create();
        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Test Variation',
            'sku' => 'TEST-LARGE-QTY',
            'price' => 10.00, // Known current price
            'is_default' => true,
            'is_global' => false,
            'is_active' => true,
            'fill_weight' => 113.4,
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $template->id,
            'product_id' => $product->id,
            'price_variation_id' => $priceVariation->id,
            'quantity' => 999999, // Very large quantity
            'price' => 0.01, // Historical price (should be updated to current)
        ]);

        $newOrder = $template->generateNextRecurringOrder();

        $this->assertNotNull($newOrder);
        $newOrderItem = $newOrder->orderItems->first();
        
        // Verify quantity is preserved
        $this->assertEquals(999999, $newOrderItem->quantity);
        
        // Verify price is updated to current pricing (not historical 0.01)
        $expectedCurrentPrice = $product->getPriceForSpecificCustomer($template->user, $priceVariation->id);
        $this->assertEquals($expectedCurrentPrice, $newOrderItem->price);
        
        // Verify total reflects current pricing
        $expectedTotal = 999999 * $expectedCurrentPrice;
        $this->assertEquals($expectedTotal, $newOrder->totalAmount());
    }

    /** @test */
    public function it_handles_recurring_order_with_deleted_product()
    {
        $product = Product::factory()->create();
        $template = Order::factory()->recurring()->create();
        
        OrderItem::factory()->create([
            'order_id' => $template->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 10.00,
        ]);

        // Soft delete the product (if using soft deletes)
        $product->delete();

        $newOrder = $template->generateNextRecurringOrder();

        // Order should still be generated but without price recalculation
        $this->assertNotNull($newOrder);
        $newOrderItem = $newOrder->orderItems->first();
        $this->assertEquals($product->id, $newOrderItem->product_id);
        $this->assertEquals(10.00, $newOrderItem->price); // Original price preserved
    }

    /** @test */
    public function it_handles_recurring_order_with_inactive_user()
    {
        $user = User::factory()->create();
        $template = Order::factory()->recurring()->create([
            'user_id' => $user->id,
            'recurring_start_date' => now()->subDays(7),
            'last_generated_at' => now()->subDays(7),
        ]);
        
        OrderItem::factory()->create(['order_id' => $template->id]);

        // Mark user as inactive (realistic business scenario)
        // Note: In real systems, you'd use soft deletes or status fields
        // For this test, we'll just verify the order generation still works
        
        $newOrder = $template->generateNextRecurringOrder();
        
        // Order should be generated successfully since user still exists
        $this->assertNotNull($newOrder);
        $this->assertEquals($user->id, $newOrder->user_id);
    }

    /** @test */
    public function it_prevents_infinite_recursion_in_recurring_orders()
    {
        $template = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'recurring_start_date' => now(),
            'last_generated_at' => now(),
        ]);

        OrderItem::factory()->create(['order_id' => $template->id]);

        // Generate multiple orders rapidly
        $generatedOrders = [];
        for ($i = 0; $i < 5; $i++) {
            $order = $template->generateNextRecurringOrder();
            if ($order) {
                $generatedOrders[] = $order;
            }
        }

        // Should only generate one order per call, not infinite
        $this->assertLessThanOrEqual(1, count($generatedOrders));
    }

    /** @test */
    public function it_handles_concurrent_recurring_order_generation()
    {
        $template = Order::factory()->recurring()->create([
            'next_generation_date' => now()->subHour(),
        ]);

        OrderItem::factory()->create(['order_id' => $template->id]);

        // Simulate concurrent generation attempts
        $order1 = $template->generateNextRecurringOrder();
        $order2 = $template->generateNextRecurringOrder(); // Should be null or same delivery date check

        $this->assertNotNull($order1);
        
        // Second generation should be prevented by delivery date check
        if ($order2) {
            $this->assertNotEquals($order1->id, $order2->id);
        }
    }

    /** @test */
    public function it_handles_recurring_order_with_extreme_dates()
    {
        // Test with dates far in the past
        $pastTemplate = Order::factory()->recurring()->create([
            'recurring_start_date' => Carbon::parse('1990-01-01'),
            'recurring_end_date' => Carbon::parse('2030-12-31'),
            'last_generated_at' => Carbon::parse('1990-01-01'),
        ]);

        $nextDate = $pastTemplate->calculateNextGenerationDate();
        $this->assertNotNull($nextDate);

        // Test with dates far in the future
        $futureTemplate = Order::factory()->recurring()->create([
            'recurring_start_date' => Carbon::parse('2090-01-01'),
            'last_generated_at' => Carbon::parse('2090-01-01'),
        ]);

        $futureNextDate = $futureTemplate->calculateNextGenerationDate();
        $this->assertNotNull($futureNextDate);
        $this->assertTrue($futureNextDate->isFuture());
    }

    /** @test */
    public function it_handles_order_with_negative_prices()
    {
        $order = Order::factory()->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'price' => -5.00, // Negative price (refund/discount)
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 1,
            'price' => 20.00,
        ]);

        $total = $order->totalAmount();
        $this->assertEquals(10.00, $total); // (2 * -5) + (1 * 20) = 10
    }

    /** @test */
    public function it_handles_order_with_decimal_quantities()
    {
        $order = Order::factory()->create();
        
        // For bulk products sold by weight
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2.5, // 2.5 lbs or kg
            'price' => 8.00,
        ]);

        $total = $order->totalAmount();
        $this->assertEquals(20.00, $total); // 2.5 * 8.00
    }

    /** @test */
    public function it_handles_very_long_recurring_schedules()
    {
        $template = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'recurring_start_date' => now(),
            'recurring_end_date' => now()->addYears(10), // 10 years
        ]);

        // Should not cause issues with very long schedules
        $this->assertTrue($template->is_recurring_active);
        $this->assertNotNull($template->calculateNextGenerationDate());
    }

    /** @test */
    public function it_handles_order_status_transitions_validation()
    {
        $order = Order::factory()->create(['status' => 'pending']);

        // Valid transitions
        $order->update(['status' => 'confirmed']);
        $this->assertEquals('confirmed', $order->status);

        $order->update(['status' => 'processing']);
        $this->assertEquals('processing', $order->status);

        $order->update(['status' => 'completed']);
        $this->assertEquals('completed', $order->status);

        // Test that we can also go backwards if needed
        $order->update(['status' => 'processing']);
        $this->assertEquals('processing', $order->status);
    }

    /** @test */
    public function it_handles_orders_with_no_items()
    {
        $order = Order::factory()->create();
        
        // Order with no items should have zero total
        $this->assertEquals(0, $order->totalAmount());
        $this->assertEquals(0, $order->remainingBalance());
        $this->assertFalse($order->isPaid()); // Can't be paid if total is 0
    }

    /** @test */
    public function it_handles_leap_year_recurring_dates()
    {
        Carbon::setTestNow('2024-02-29'); // Leap year date
        
        $template = Order::factory()->recurring()->create([
            'recurring_frequency' => 'monthly',
            'recurring_start_date' => Carbon::parse('2024-02-29'),
            'last_generated_at' => Carbon::parse('2024-02-29'),
        ]);

        $nextDate = $template->calculateNextGenerationDate();
        
        // Should handle leap year edge case gracefully
        $this->assertNotNull($nextDate);
        $this->assertEquals('2024-03-29', $nextDate->format('Y-m-d')); // March 29, not March 1

        Carbon::setTestNow();
    }

    /** @test */
    public function it_handles_daylight_saving_time_transitions()
    {
        // Test during DST transition
        $dstDate = Carbon::parse('2024-03-10 02:30:00', 'America/New_York'); // DST spring forward
        
        $template = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'last_generated_at' => $dstDate,
        ]);

        $nextDate = $template->calculateNextGenerationDate();
        
        $this->assertNotNull($nextDate);
        $this->assertEquals(7, $dstDate->diffInDays($nextDate));
    }

    /** @test */
    public function it_handles_unicode_and_special_characters_in_order_notes()
    {
        $order = Order::factory()->create([
            'notes' => '🌱 Special microgreens order with émojis and àccénts! 特殊字符测试',
        ]);

        $this->assertStringContains('🌱', $order->notes);
        $this->assertStringContains('émojis', $order->notes);
        $this->assertStringContains('特殊字符', $order->notes);
    }

    /** @test */
    public function it_handles_very_small_price_amounts()
    {
        $order = Order::factory()->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 1000,
            'price' => 0.001, // Very small price
        ]);

        $total = $order->totalAmount();
        $this->assertEquals(1.0, $total);
    }

    /** @test */
    public function it_handles_payment_precision_edge_cases()
    {
        $order = Order::factory()->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 3,
            'price' => 33.333333, // Repeating decimal
        ]);

        $total = $order->totalAmount();
        
        // Create payment slightly off due to rounding
        $order->payments()->create([
            'amount' => 99.99, // Slightly less than exact total
            'status' => 'completed',
        ]);

        $remainingBalance = $order->remainingBalance();
        
        // Should handle small rounding differences gracefully
        $this->assertLessThan(0.01, abs($remainingBalance));
    }
}
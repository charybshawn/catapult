<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_be_created_with_basic_attributes()
    {
        $user = User::factory()->create();
        
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'delivery_date' => now()->addDays(3),
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($user->id, $order->user_id);
    }

    /** @test */
    public function it_automatically_sets_status_to_template_for_recurring_orders()
    {
        $order = Order::factory()->create([
            'is_recurring' => true,
            'status' => null,
        ]);

        $this->assertEquals('template', $order->status);
    }

    /** @test */
    public function it_automatically_sets_status_to_pending_for_non_recurring_orders()
    {
        $order = Order::factory()->create([
            'is_recurring' => false,
            'status' => null,
        ]);

        $this->assertEquals('pending', $order->status);
    }

    /** @test */
    public function it_calculates_total_amount_correctly()
    {
        $order = Order::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        
        // Create price variations
        $priceVar1 = PriceVariation::create([
            'product_id' => $product1->id,
            'name' => 'Test Variation 1',
            'sku' => 'TEST1',
            'price' => 10.50,
            'is_default' => true,
            'is_global' => false,
            'is_active' => true,
            'fill_weight_grams' => 113.4,
        ]);
        
        $priceVar2 = PriceVariation::create([
            'product_id' => $product2->id,
            'name' => 'Test Variation 2',
            'sku' => 'TEST2',
            'price' => 5.25,
            'is_default' => true,
            'is_global' => false,
            'is_active' => true,
            'fill_weight_grams' => 113.4,
        ]);
        
        // Create order items manually with controlled prices
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'price_variation_id' => $priceVar1->id,
            'quantity' => 2,
            'price' => 10.50,
        ]);
        
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'price_variation_id' => $priceVar2->id,
            'quantity' => 3,
            'price' => 5.25,
        ]);

        $expectedTotal = (2 * 10.50) + (3 * 5.25); // 21.00 + 15.75 = 36.75
        $this->assertEquals($expectedTotal, $order->totalAmount());
    }

    /** @test */
    public function it_correctly_identifies_recurring_templates()
    {
        $recurringTemplate = Order::factory()->recurring()->create();
        $generatedOrder = Order::factory()->generatedFromRecurring($recurringTemplate)->create();
        $regularOrder = Order::factory()->create();

        $this->assertTrue($recurringTemplate->isRecurringTemplate());
        $this->assertFalse($generatedOrder->isRecurringTemplate());
        $this->assertFalse($regularOrder->isRecurringTemplate());
    }

    /** @test */
    public function it_correctly_identifies_b2b_recurring_templates()
    {
        $b2bRecurring = Order::factory()->b2bRecurring()->create();
        $regularRecurring = Order::factory()->recurring()->create([
            'order_type' => 'website_immediate',
        ]);

        $this->assertTrue($b2bRecurring->isB2BRecurringTemplate());
        $this->assertFalse($regularRecurring->isB2BRecurringTemplate());
    }

    /** @test */
    public function it_correctly_identifies_generated_orders()
    {
        $template = Order::factory()->recurring()->create();
        $generatedOrder = Order::factory()->generatedFromRecurring($template)->create();
        $regularOrder = Order::factory()->create();

        $this->assertTrue($generatedOrder->isGeneratedFromRecurring());
        $this->assertFalse($template->isGeneratedFromRecurring());
        $this->assertFalse($regularOrder->isGeneratedFromRecurring());
    }

    /** @test */
    public function it_calculates_next_generation_date_for_weekly_frequency()
    {
        $startDate = now();
        $order = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'recurring_start_date' => $startDate,
            'last_generated_at' => $startDate,
        ]);

        $nextDate = $order->calculateNextGenerationDate();
        
        $this->assertNotNull($nextDate);
        $this->assertEquals($startDate->copy()->addWeek()->format('Y-m-d'), $nextDate->format('Y-m-d'));
    }

    /** @test */
    public function it_calculates_next_generation_date_for_biweekly_frequency()
    {
        $startDate = now();
        $order = Order::factory()->recurring()->create([
            'recurring_frequency' => 'biweekly',
            'recurring_interval' => 2,
            'recurring_start_date' => $startDate,
            'last_generated_at' => $startDate,
        ]);

        $nextDate = $order->calculateNextGenerationDate();
        
        $this->assertNotNull($nextDate);
        $this->assertEquals($startDate->copy()->addWeeks(2)->format('Y-m-d'), $nextDate->format('Y-m-d'));
    }

    /** @test */
    public function it_calculates_next_generation_date_for_monthly_frequency()
    {
        $startDate = now();
        $order = Order::factory()->recurring()->create([
            'recurring_frequency' => 'monthly',
            'recurring_start_date' => $startDate,
            'last_generated_at' => $startDate,
        ]);

        $nextDate = $order->calculateNextGenerationDate();
        
        $this->assertNotNull($nextDate);
        $this->assertEquals($startDate->copy()->addMonth()->format('Y-m-d'), $nextDate->format('Y-m-d'));
    }

    /** @test */
    public function it_returns_null_next_generation_date_for_inactive_recurring_orders()
    {
        $order = Order::factory()->recurring()->create([
            'is_recurring_active' => false,
        ]);

        $this->assertNull($order->calculateNextGenerationDate());
    }

    /** @test */
    public function it_returns_null_next_generation_date_for_non_recurring_orders()
    {
        $order = Order::factory()->create([
            'is_recurring' => false,
        ]);

        $this->assertNull($order->calculateNextGenerationDate());
    }

    /** @test */
    public function it_calculates_remaining_balance_correctly()
    {
        $order = Order::factory()->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'price' => 25.00,
        ]);

        // Create some payments
        $order->payments()->create(['amount' => 30.00, 'status' => 'completed']);
        $order->payments()->create(['amount' => 10.00, 'status' => 'pending']); // Should not count

        $expectedBalance = 50.00 - 30.00; // Total - completed payments
        $this->assertEquals($expectedBalance, $order->remainingBalance());
    }

    /** @test */
    public function it_correctly_determines_if_order_is_paid()
    {
        $order = Order::factory()->create();
        
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'price' => 25.00,
        ]);

        // Not paid yet
        $this->assertFalse($order->isPaid());

        // Partially paid
        $order->payments()->create(['amount' => 30.00, 'status' => 'completed']);
        $this->assertFalse($order->isPaid());

        // Fully paid
        $order->payments()->create(['amount' => 20.00, 'status' => 'completed']);
        $this->assertTrue($order->isPaid());
    }

    /** @test */
    public function it_gets_customer_type_from_order_when_set()
    {
        $user = User::factory()->create(['customer_type' => 'retail']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_type' => 'wholesale',
        ]);

        $this->assertEquals('wholesale', $order->customer_type);
    }

    /** @test */
    public function it_gets_customer_type_from_user_when_not_set_on_order()
    {
        $user = User::factory()->create(['customer_type' => 'wholesale']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_type' => null,
        ]);

        $this->assertEquals('wholesale', $order->customer_type);
    }

    /** @test */
    public function it_defaults_customer_type_to_retail()
    {
        $user = User::factory()->create(); // Will use database default 'retail'
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_type' => null,
        ]);

        $this->assertEquals('retail', $order->customer_type);
    }

    /** @test */
    public function it_formats_recurring_frequency_display_correctly()
    {
        $weeklyOrder = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
        ]);

        $biweeklyOrder = Order::factory()->recurring()->create([
            'recurring_frequency' => 'biweekly',
            'recurring_interval' => 3,
        ]);

        $monthlyOrder = Order::factory()->recurring()->create([
            'recurring_frequency' => 'monthly',
        ]);

        $nonRecurringOrder = Order::factory()->create([
            'is_recurring' => false,
        ]);

        $this->assertEquals('Weekly', $weeklyOrder->recurring_frequency_display);
        $this->assertEquals('Every 3 weeks', $biweeklyOrder->recurring_frequency_display);
        $this->assertEquals('Monthly', $monthlyOrder->recurring_frequency_display);
        $this->assertEquals('Not recurring', $nonRecurringOrder->recurring_frequency_display);
    }

    /** @test */
    public function it_counts_generated_orders_correctly()
    {
        $template = Order::factory()->recurring()->create();
        
        // Create some generated orders
        Order::factory()->generatedFromRecurring($template)->create();
        Order::factory()->generatedFromRecurring($template)->create();
        Order::factory()->generatedFromRecurring($template)->create();

        $this->assertEquals(3, $template->generated_orders_count);
    }

    /** @test */
    public function it_correctly_determines_billing_requirements()
    {
        $immediateOrder = Order::factory()->create([
            'order_type' => 'website_immediate',
        ]);

        $farmersMarketOrder = Order::factory()->create([
            'order_type' => 'farmers_market',
            'requires_invoice' => false,
        ]);

        $b2bOrder = Order::factory()->create([
            'order_type' => 'b2b',
            'billing_frequency' => 'monthly',
        ]);

        $this->assertTrue($immediateOrder->requiresImmediateInvoicing());
        $this->assertTrue($farmersMarketOrder->shouldBypassInvoicing());
        $this->assertTrue($b2bOrder->isConsolidatedBilling());
    }

    /** @test */
    public function it_auto_sets_customer_type_from_user_on_save()
    {
        $wholesaleUser = User::factory()->create(['customer_type' => 'wholesale']);
        
        $order = new Order([
            'user_id' => $wholesaleUser->id,
            'harvest_date' => now()->addDays(1),
            'delivery_date' => now()->addDays(2),
        ]);
        
        $order->save();
        
        $this->assertEquals('wholesale', $order->customer_type);
    }

    /** @test */
    public function it_auto_sets_recurring_start_date_from_harvest_date()
    {
        $harvestDate = now()->addDays(5);
        
        $order = Order::factory()->create([
            'is_recurring' => true,
            'harvest_date' => $harvestDate,
            'recurring_start_date' => null,
        ]);

        $this->assertEquals($harvestDate->format('Y-m-d'), $order->recurring_start_date->format('Y-m-d'));
    }
}
<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Comprehensive unit tests for Order model in agricultural microgreens business.
 * 
 * Tests complete Order model functionality including creation, pricing calculations,
 * recurring order templates, payment tracking, and customer type management for
 * agricultural business workflows. Validates order lifecycle from creation through
 * completion for microgreens production and sales management.
 *
 * @covers \App\Models\Order
 * @group unit
 * @group orders
 * @group models
 * @group agricultural-testing
 * 
 * @business_context Agricultural order management for microgreens sales and production
 * @test_category Comprehensive unit tests for Order model functionality
 * @agricultural_workflow Order lifecycle management for microgreens business operations
 */
class OrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test basic order creation with essential agricultural business attributes.
     * 
     * Validates that orders can be created with fundamental agricultural workflow
     * properties including user assignment, status tracking, and delivery scheduling
     * for microgreens production and customer management.
     *
     * @test
     * @return void
     * @agricultural_scenario Basic order creation for microgreens customer
     * @business_validation Ensures orders contain required agricultural business fields
     */
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

    /**
     * Test automatic template status assignment for recurring agricultural orders.
     * 
     * Validates that recurring orders automatically receive template status for
     * agricultural subscription management and automated production planning.
     * Templates enable consistent microgreens delivery scheduling.
     *
     * @test
     * @return void
     * @agricultural_scenario Recurring microgreens subscription template creation
     * @business_logic Templates enable automated agricultural production planning
     */
    public function it_automatically_sets_status_to_template_for_recurring_orders()
    {
        $order = Order::factory()->create([
            'is_recurring' => true,
            'status' => null,
        ]);

        $this->assertEquals('template', $order->status);
    }

    /**
     * Test automatic pending status assignment for one-time agricultural orders.
     * 
     * Validates that non-recurring orders automatically receive pending status for
     * immediate agricultural processing and production scheduling. Ensures proper
     * workflow initiation for individual microgreens orders.
     *
     * @test
     * @return void
     * @agricultural_scenario One-time microgreens order requiring immediate processing
     * @business_logic Pending status triggers agricultural production workflow
     */
    public function it_automatically_sets_status_to_pending_for_non_recurring_orders()
    {
        $order = Order::factory()->create([
            'is_recurring' => false,
            'status' => null,
        ]);

        $this->assertEquals('pending', $order->status);
    }

    /**
     * Test order total calculation with agricultural product pricing variations.
     * 
     * Validates that orders correctly calculate totals from product items with
     * different price variations, quantities, and agricultural pricing structures.
     * Tests complex microgreens pricing calculations for accurate billing.
     *
     * @test
     * @return void
     * @agricultural_scenario Multi-product order with different microgreens varieties
     * @business_validation Ensures accurate pricing for agricultural products
     */
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

    /**
     * Test recurring template identification for agricultural subscription management.
     * 
     * Validates that orders can be properly identified as recurring templates,
     * generated instances, or regular orders for agricultural workflow management.
     * Essential for microgreens subscription and production planning.
     *
     * @test
     * @return void
     * @agricultural_scenario Identifying template vs generated subscription orders
     * @business_logic Templates vs instances enable agricultural planning workflows
     */
    public function it_correctly_identifies_recurring_templates()
    {
        $recurringTemplate = Order::factory()->recurring()->create();
        $generatedOrder = Order::factory()->generatedFromRecurring($recurringTemplate)->create();
        $regularOrder = Order::factory()->create();

        $this->assertTrue($recurringTemplate->isRecurringTemplate());
        $this->assertFalse($generatedOrder->isRecurringTemplate());
        $this->assertFalse($regularOrder->isRecurringTemplate());
    }

    /**
     * Test B2B recurring template identification for wholesale agricultural sales.
     * 
     * Validates that B2B recurring orders are properly identified for wholesale
     * agricultural workflow management and bulk microgreens production planning.
     * Distinguishes wholesale from retail subscription patterns.
     *
     * @test
     * @return void
     * @agricultural_scenario Wholesale microgreens subscription management
     * @business_logic B2B templates enable bulk agricultural production planning
     */
    public function it_correctly_identifies_b2b_templates()
    {
        $b2bRecurring = Order::factory()->b2bRecurring()->create();
        $regularRecurring = Order::factory()->recurring()->create([
            'order_type' => 'website_immediate',
        ]);

        $this->assertTrue($b2bRecurring->isB2BRecurringTemplate());
        $this->assertFalse($regularRecurring->isB2BRecurringTemplate());
    }

    /**
     * Test generated order identification for automated agricultural scheduling.
     * 
     * Validates that orders generated from recurring templates are properly
     * identified for agricultural workflow tracking and automated production
     * scheduling. Essential for microgreens subscription fulfillment.
     *
     * @test
     * @return void
     * @agricultural_scenario Automated order generation from subscription templates
     * @business_logic Generated orders trigger agricultural production workflows
     */
    public function it_correctly_identifies_generated_orders()
    {
        $template = Order::factory()->recurring()->create();
        $generatedOrder = Order::factory()->generatedFromRecurring($template)->create();
        $regularOrder = Order::factory()->create();

        $this->assertTrue($generatedOrder->isGeneratedFromRecurring());
        $this->assertFalse($template->isGeneratedFromRecurring());
        $this->assertFalse($regularOrder->isGeneratedFromRecurring());
    }

    /**
     * Test weekly recurring order generation date calculation for agricultural scheduling.
     * 
     * Validates that weekly recurring orders correctly calculate next generation dates
     * for agricultural subscription management and automated production planning.
     * Tests consistent microgreens delivery scheduling.
     *
     * @test
     * @return void
     * @agricultural_scenario Weekly microgreens subscription delivery scheduling
     * @business_logic Weekly frequency enables regular agricultural production cycles
     */
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

    /**
     * Test biweekly recurring order generation date calculation for agricultural planning.
     * 
     * Validates that biweekly recurring orders correctly calculate next generation dates
     * with custom intervals for agricultural subscription management and production
     * planning. Tests flexible microgreens delivery frequencies.
     *
     * @test
     * @return void
     * @agricultural_scenario Biweekly microgreens subscription with custom intervals
     * @business_logic Biweekly frequency provides flexible agricultural delivery scheduling
     */
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

    /**
     * Test monthly recurring order generation date calculation for agricultural scheduling.
     * 
     * Validates that monthly recurring orders correctly calculate next generation dates
     * for long-term agricultural subscription management and bulk production planning.
     * Tests consistent monthly microgreens deliveries.
     *
     * @test
     * @return void
     * @agricultural_scenario Monthly bulk microgreens subscription scheduling
     * @business_logic Monthly frequency enables long-term agricultural planning cycles
     */
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

    /**
     * Test null generation date for inactive recurring agricultural orders.
     * 
     * Validates that inactive recurring orders return null generation dates to
     * prevent unwanted agricultural production scheduling and subscription processing.
     * Ensures proper agricultural workflow control.
     *
     * @test
     * @return void
     * @agricultural_scenario Paused microgreens subscription preventing production
     * @business_logic Inactive status prevents automated agricultural scheduling
     */
    public function it_returns_null_next_generation_date_for_inactive_recurring_orders()
    {
        $order = Order::factory()->recurring()->create([
            'is_recurring_active' => false,
        ]);

        $this->assertNull($order->calculateNextGenerationDate());
    }

    /**
     * Test null generation date for non-recurring agricultural orders.
     * 
     * Validates that non-recurring orders return null generation dates since they
     * are not part of agricultural subscription workflows and should not trigger
     * automated production scheduling.
     *
     * @test
     * @return void
     * @agricultural_scenario One-time microgreens order without subscription
     * @business_logic Non-recurring orders do not participate in automated scheduling
     */
    public function it_returns_null_next_generation_date_for_non_recurring_orders()
    {
        $order = Order::factory()->create([
            'is_recurring' => false,
        ]);

        $this->assertNull($order->calculateNextGenerationDate());
    }

    /**
     * Test remaining balance calculation for agricultural order payment tracking.
     * 
     * Validates that orders correctly calculate remaining balances from total amount
     * and completed payments for agricultural financial management. Tests payment
     * status filtering for accurate microgreens business accounting.
     *
     * @test
     * @return void
     * @agricultural_scenario Partial payment tracking for microgreens order
     * @business_validation Ensures accurate payment balance calculations
     */
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

    /**
     * Test order payment status determination for agricultural financial management.
     * 
     * Validates that orders correctly determine paid status based on total amount
     * and completed payments for agricultural business financial tracking.
     * Tests payment completion validation for microgreens orders.
     *
     * @test
     * @return void
     * @agricultural_scenario Payment completion tracking for microgreens sales
     * @business_validation Ensures accurate payment status determination
     */
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

    /**
     * Test customer type retrieval from order for agricultural customer management.
     * 
     * Validates that orders correctly return customer type when explicitly set,
     * overriding user defaults for agricultural customer classification and
     * pricing management in microgreens business workflows.
     *
     * @test
     * @return void
     * @agricultural_scenario Order-specific customer type override for pricing
     * @business_logic Order-level customer type overrides user default
     */
    public function it_gets_customer_type_from_order_when_set()
    {
        $user = User::factory()->create(['customer_type' => 'retail']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_type' => 'wholesale',
        ]);

        $this->assertEquals('wholesale', $order->customer_type);
    }

    /**
     * Test customer type inheritance from user for agricultural customer management.
     * 
     * Validates that orders correctly inherit customer type from user when not
     * explicitly set for consistent agricultural customer classification and
     * pricing management in microgreens business operations.
     *
     * @test
     * @return void
     * @agricultural_scenario Default customer type inheritance from user profile
     * @business_logic Orders inherit user customer type when not explicitly set
     */
    public function it_gets_customer_type_from_user_when_not_set_on_order()
    {
        $user = User::factory()->create(['customer_type' => 'wholesale']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_type' => null,
        ]);

        $this->assertEquals('wholesale', $order->customer_type);
    }

    /**
     * Test default retail customer type for agricultural customer management.
     * 
     * Validates that orders default to retail customer type when neither order
     * nor user specify a type for consistent agricultural customer classification
     * and pricing management in microgreens business workflows.
     *
     * @test
     * @return void
     * @agricultural_scenario Default retail classification for new customers
     * @business_logic Retail is default customer type for agricultural orders
     */
    public function it_defaults_customer_type_to_retail()
    {
        $user = User::factory()->create(); // Will use database default 'retail'
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'customer_type' => null,
        ]);

        $this->assertEquals('retail', $order->customer_type);
    }

    /**
     * Test recurring frequency display formatting for agricultural subscription UI.
     * 
     * Validates that recurring orders correctly format frequency displays for
     * agricultural subscription management interfaces and customer communication.
     * Tests user-friendly microgreens subscription frequency presentation.
     *
     * @test
     * @return void
     * @agricultural_scenario Subscription frequency display in customer interface
     * @business_logic Human-readable frequency display for agricultural subscriptions
     */
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

    /**
     * Test generated order count tracking for agricultural subscription management.
     * 
     * Validates that recurring templates correctly count generated orders for
     * agricultural subscription tracking and performance analysis. Tests
     * subscription activity monitoring for microgreens business insights.
     *
     * @test
     * @return void
     * @agricultural_scenario Subscription template activity tracking
     * @business_logic Generated order count provides subscription performance metrics
     */
    public function it_counts_generated_orders_correctly()
    {
        $template = Order::factory()->recurring()->create();
        
        // Create some generated orders
        Order::factory()->generatedFromRecurring($template)->create();
        Order::factory()->generatedFromRecurring($template)->create();
        Order::factory()->generatedFromRecurring($template)->create();

        $this->assertEquals(3, $template->generated_orders_count);
    }

    /**
     * Test billing requirement determination for agricultural order processing.
     * 
     * Validates that orders correctly determine billing requirements based on order type
     * for agricultural workflow management including immediate invoicing, bypassing,
     * and consolidated billing for microgreens business operations.
     *
     * @test
     * @return void
     * @agricultural_scenario Different billing requirements for order types
     * @business_logic Order type determines agricultural billing workflow
     */
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

    /**
     * Test automatic customer type assignment from user on agricultural order save.
     * 
     * Validates that orders automatically inherit customer type from user during
     * save operations for consistent agricultural customer classification and
     * pricing management in microgreens business workflows.
     *
     * @test
     * @return void
     * @agricultural_scenario Automatic customer type assignment during order creation
     * @business_logic Orders automatically inherit user customer type on save
     */
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

    /**
     * Test automatic recurring start date assignment from harvest date for agricultural scheduling.
     * 
     * Validates that recurring orders automatically set start date from harvest date
     * for agricultural subscription scheduling and automated production planning.
     * Ensures consistent microgreens delivery scheduling.
     *
     * @test
     * @return void
     * @agricultural_scenario Recurring subscription start date from harvest schedule
     * @business_logic Harvest date determines recurring subscription start timing
     */
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
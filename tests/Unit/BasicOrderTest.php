<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for basic Order model functionality in agricultural microgreens production.
 * 
 * Tests core Order model behavior including creation, validation, and basic calculations
 * for agricultural business workflows. Validates fundamental order properties like
 * harvest scheduling, customer types, and recurring order template functionality.
 *
 * @covers \App\Models\Order
 * @group unit
 * @group orders
 * @group agricultural-testing
 * 
 * @business_context Agricultural order management for microgreens production
 * @test_category Unit tests for Order model core functionality
 * @agricultural_workflow Order lifecycle from creation to harvest scheduling
 */
class BasicOrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test basic order creation with essential agricultural business attributes.
     * 
     * Validates that orders can be created with proper agricultural workflow scheduling
     * including harvest and delivery dates, customer classification, and order processing
     * requirements for microgreens production.
     *
     * @test
     * @return void
     * @agricultural_scenario Standard retail customer order with immediate processing
     * @business_validation Ensures orders contain required fields for agricultural scheduling
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_basic_order()
    {
        $user = User::factory()->create();
        
        $order = Order::create([
            'user_id' => $user->id,
            'harvest_date' => now()->addDays(5),
            'delivery_date' => now()->addDays(6),
            'status' => 'pending',
            'customer_type' => 'retail',
            'order_type' => 'website_immediate',
            'billing_frequency' => 'immediate',
            'requires_invoice' => true,
            'is_recurring' => false,
            'is_recurring_active' => false,
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals('retail', $order->customer_type);
    }

    /**
     * Test order total calculation for empty orders in agricultural context.
     * 
     * Validates that orders without items correctly calculate to zero total,
     * which is important for draft orders and order templates in agricultural
     * workflow management before products are added.
     *
     * @test
     * @return void
     * @agricultural_scenario Empty order draft before product selection
     * @business_logic Total calculation should handle empty orders gracefully
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_total_amount_with_zero_items()
    {
        $user = User::factory()->create();
        
        $order = Order::create([
            'user_id' => $user->id,
            'harvest_date' => now()->addDays(5),
            'delivery_date' => now()->addDays(6),
            'status' => 'pending',
            'customer_type' => 'retail',
            'order_type' => 'website_immediate',
            'billing_frequency' => 'immediate',
            'requires_invoice' => true,
            'is_recurring' => false,
            'is_recurring_active' => false,
        ]);

        $this->assertEquals(0, $order->totalAmount());
    }

    /**
     * Test recurring order template identification for agricultural subscription workflows.
     * 
     * Validates that recurring order templates are properly identified and configured
     * for automated agricultural production scheduling. Tests template status recognition
     * and recurring frequency settings for ongoing customer relationships.
     *
     * @test
     * @return void
     * @agricultural_scenario Recurring weekly microgreens subscription for regular customer
     * @business_logic Template orders enable automated agricultural planning and production
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_identifies_recurring_templates()
    {
        $user = User::factory()->create();
        
        $template = Order::create([
            'user_id' => $user->id,
            'harvest_date' => now()->addDays(5),
            'delivery_date' => now()->addDays(6),
            'status' => 'template',
            'customer_type' => 'retail',
            'order_type' => 'website_immediate',
            'billing_frequency' => 'immediate',
            'requires_invoice' => true,
            'is_recurring' => true,
            'is_recurring_active' => true,
            'recurring_frequency' => 'weekly',
        ]);

        $this->assertTrue($template->isRecurringTemplate());
        $this->assertTrue($template->is_recurring);
        $this->assertEquals('template', $template->status);
    }
}
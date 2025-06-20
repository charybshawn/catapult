<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BasicOrderTest extends TestCase
{
    use RefreshDatabase;

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
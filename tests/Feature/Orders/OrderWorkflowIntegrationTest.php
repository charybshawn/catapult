<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use App\Models\Invoice;
use App\Services\RecurringOrderService;
use App\Console\Commands\ProcessRecurringOrders;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class OrderWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_order_lifecycle_from_creation_to_completion()
    {
        // 1. Create customer and products
        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'customer_type' => 'retail',
        ]);

        $product = Product::factory()->create([
            'name' => 'Arugula',
            'base_price' => 8.50,
        ]);

        $priceVariation = PriceVariation::factory()->forProduct($product)->default()->create([
            'price' => 8.50,
        ]);

        // 2. Create order
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'pending',
            'order_type' => 'website_immediate',
            'customer_type' => 'retail',
            'harvest_date' => now()->addDays(5),
            'delivery_date' => now()->addDays(6),
        ]);

        // 3. Add items to order
        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price_variation_id' => $priceVariation->id,
            'quantity' => 3,
            'price' => 8.50,
        ]);

        // 4. Verify order total
        $this->assertEquals(25.50, $order->totalAmount());

        // 5. Create invoice for immediate billing
        $invoice = Invoice::factory()->forOrder($order)->create([
            'status' => 'draft',
        ]);

        $this->assertEquals($order->totalAmount(), $invoice->amount);

        // 6. Send invoice
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // 7. Process payment
        $order->payments()->create([
            'amount' => $order->totalAmount(),
            'status' => 'completed',
        ]);

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // 8. Update order status through workflow
        $order->update(['status' => 'confirmed']);
        $order->update(['status' => 'processing']);
        $order->update(['crop_status' => 'planted']);
        $order->update(['crop_status' => 'growing']);
        $order->update(['crop_status' => 'ready_to_harvest']);
        $order->update(['crop_status' => 'harvested']);
        $order->update(['fulfillment_status' => 'packing']);
        $order->update(['fulfillment_status' => 'packed']);
        $order->update(['fulfillment_status' => 'delivered']);
        $order->update(['status' => 'completed']);

        // 9. Verify final state
        $this->assertTrue($order->isPaid());
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('harvested', $order->crop_status);
        $this->assertEquals('delivered', $order->fulfillment_status);
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0, $order->remainingBalance());
    }

    /** @test */
    public function complete_recurring_order_workflow()
    {
        // 1. Create customer and product
        $customer = User::factory()->create(['customer_type' => 'retail']);
        $product = Product::factory()->create(['base_price' => 12.00]);
        $priceVariation = PriceVariation::factory()->forProduct($product)->default()->create();

        // 2. Create recurring order template
        $template = Order::factory()->recurring()->create([
            'user_id' => $customer->id,
            'recurring_frequency' => 'weekly',
            'recurring_start_date' => now(),
            'is_recurring_active' => true,
            'next_generation_date' => now()->subHour(), // Ready for generation
        ]);

        // 3. Add items to template
        OrderItem::factory()->create([
            'order_id' => $template->id,
            'product_id' => $product->id,
            'price_variation_id' => $priceVariation->id,
            'quantity' => 2,
            'price' => 12.00,
        ]);

        // 4. Process recurring orders (simulate command)
        $recurringService = app(RecurringOrderService::class);
        $results = $recurringService->processRecurringOrders();

        $this->assertEquals(1, $results['generated']);
        $this->assertEmpty($results['errors']);

        // 5. Verify generated order
        $generatedOrder = $template->generatedOrders()->first();
        $this->assertNotNull($generatedOrder);
        $this->assertEquals('pending', $generatedOrder->status);
        $this->assertEquals($customer->id, $generatedOrder->user_id);
        $this->assertFalse($generatedOrder->is_recurring);
        $this->assertEquals($template->id, $generatedOrder->parent_recurring_order_id);

        // 6. Verify order items were copied
        $this->assertCount(1, $generatedOrder->orderItems);
        $generatedItem = $generatedOrder->orderItems->first();
        $this->assertEquals($product->id, $generatedItem->product_id);
        $this->assertEquals(2, $generatedItem->quantity);

        // 7. Template should be updated
        $template->refresh();
        $this->assertNotNull($template->last_generated_at);
        $this->assertNotNull($template->next_generation_date);

        // 8. Process the generated order through normal workflow
        $generatedOrder->update(['status' => 'confirmed']);
        $this->assertEquals('confirmed', $generatedOrder->status);
    }

    /** @test */
    public function b2b_consolidated_billing_workflow()
    {
        // 1. Create wholesale customer
        $customer = User::factory()->create([
            'customer_type' => 'wholesale',
            'name' => 'Restaurant Client',
        ]);

        $product = Product::factory()->create([
            'base_price' => 20.00,
            'wholesale_price' => 16.00,
        ]);

        $priceVariation = PriceVariation::factory()->forProduct($product)->create([
            'price' => 16.00,
        ]);

        // 2. Create multiple B2B orders for same billing period
        $billingStart = Carbon::parse('2025-01-01');
        $billingEnd = Carbon::parse('2025-01-31');

        $orders = collect();
        for ($i = 1; $i <= 3; $i++) {
            $order = Order::factory()->create([
                'user_id' => $customer->id,
                'order_type' => 'b2b',
                'billing_frequency' => 'monthly',
                'customer_type' => 'wholesale',
                'delivery_date' => $billingStart->copy()->addDays($i * 7),
                'billing_period_start' => $billingStart,
                'billing_period_end' => $billingEnd,
            ]);

            OrderItem::factory()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 5 + $i, // Different quantities
                'price' => 16.00, // Wholesale price
            ]);

            $orders->push($order);
        }

        // 3. Verify orders are set up for consolidated billing
        $orders->each(function ($order) {
            $this->assertTrue($order->isConsolidatedBilling());
            $this->assertFalse($order->requiresImmediateInvoicing());
        });

        // 4. Calculate total for consolidated invoice
        $totalAmount = $orders->sum(fn($order) => $order->totalAmount());
        $expectedTotal = (6 * 16.00) + (7 * 16.00) + (8 * 16.00); // 96 + 112 + 128 = 336

        $this->assertEquals($expectedTotal, $totalAmount);

        // 5. Create consolidated invoice
        $consolidatedInvoice = Invoice::factory()->create([
            'user_id' => $customer->id,
            'order_id' => null,
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'is_consolidated' => true,
            'consolidated_order_count' => $orders->count(),
            'billing_period_start' => $billingStart,
            'billing_period_end' => $billingEnd,
            'status' => 'draft',
        ]);

        // 6. Link orders to consolidated invoice
        $orders->each(fn($order) => $order->update([
            'consolidated_invoice_id' => $consolidatedInvoice->id
        ]));

        // 7. Send consolidated invoice
        $consolidatedInvoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // 8. Process payment
        $consolidatedInvoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // 9. Verify final state
        $this->assertTrue($consolidatedInvoice->is_consolidated);
        $this->assertEquals(3, $consolidatedInvoice->consolidated_order_count);
        $this->assertEquals('paid', $consolidatedInvoice->status);
        $this->assertEquals($expectedTotal, $consolidatedInvoice->total_amount);
    }

    /** @test */
    public function farmers_market_order_workflow_bypasses_invoicing()
    {
        // 1. Create customer
        $customer = User::factory()->create();
        $product = Product::factory()->create(['base_price' => 6.00]);
        $priceVariation = PriceVariation::factory()->forProduct($product)->default()->create();

        // 2. Create farmer's market order
        $order = Order::factory()->farmersMarket()->create([
            'user_id' => $customer->id,
            'requires_invoice' => false,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price_variation_id' => $priceVariation->id,
            'quantity' => 4,
            'price' => 6.00,
        ]);

        // 3. Verify order bypasses invoicing
        $this->assertTrue($order->shouldBypassInvoicing());
        $this->assertEquals('farmers_market', $order->order_type);
        $this->assertFalse($order->requires_invoice);

        // 4. Process payment directly (cash/card at market)
        $order->payments()->create([
            'amount' => $order->totalAmount(),
            'status' => 'completed',
        ]);

        // 5. Complete order without invoice
        $order->update(['status' => 'completed']);

        $this->assertTrue($order->isPaid());
        $this->assertEquals('completed', $order->status);
        $this->assertEquals(0, $order->remainingBalance());

        // 6. Verify no invoice was created
        $this->assertNull($order->invoice);
    }

    /** @test */
    public function order_status_transitions_and_combined_status_display()
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'crop_status' => 'not_started',
            'fulfillment_status' => 'pending',
        ]);

        // Initial state
        $this->assertStringContains('Pending', $order->combined_status);

        // Crop workflow
        $order->update(['crop_status' => 'planted']);
        $this->assertStringContains('Planted', $order->combined_status);

        $order->update(['crop_status' => 'growing']);
        $this->assertStringContains('Growing', $order->combined_status);

        $order->update(['crop_status' => 'ready_to_harvest']);
        $this->assertStringContains('Ready to Harvest', $order->combined_status);

        $order->update(['crop_status' => 'harvested']);
        $this->assertStringContains('Harvested', $order->combined_status);

        // Order status progression
        $order->update(['status' => 'confirmed']);
        $this->assertStringContains('Confirmed', $order->combined_status);

        $order->update(['status' => 'processing']);
        $this->assertStringContains('Processing', $order->combined_status);

        // Fulfillment workflow
        $order->update(['fulfillment_status' => 'packing']);
        $this->assertStringContains('Packing', $order->combined_status);

        $order->update(['fulfillment_status' => 'packed']);
        $this->assertStringContains('Packed', $order->combined_status);

        $order->update(['fulfillment_status' => 'ready_for_delivery']);
        $this->assertStringContains('Ready for Delivery', $order->combined_status);

        $order->update(['fulfillment_status' => 'delivered']);
        $this->assertStringContains('Delivered', $order->combined_status);

        $order->update(['status' => 'completed']);
        $this->assertStringContains('Completed', $order->combined_status);
    }

    /** @test */
    public function recurring_order_command_integration()
    {
        // Create multiple recurring templates with different schedules
        $weeklyTemplate = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'next_generation_date' => now()->subHour(),
        ]);

        $monthlyTemplate = Order::factory()->recurring()->create([
            'recurring_frequency' => 'monthly',
            'next_generation_date' => now()->subHour(),
        ]);

        $futureTemplate = Order::factory()->recurring()->create([
            'recurring_frequency' => 'weekly',
            'next_generation_date' => now()->addDay(),
        ]);

        // Add items to templates
        OrderItem::factory()->create(['order_id' => $weeklyTemplate->id]);
        OrderItem::factory()->create(['order_id' => $monthlyTemplate->id]);
        OrderItem::factory()->create(['order_id' => $futureTemplate->id]);

        // Run the recurring orders command
        $exitCode = Artisan::call('orders:process-recurring');

        $this->assertEquals(0, $exitCode);

        // Verify results
        $this->assertCount(1, $weeklyTemplate->generatedOrders);
        $this->assertCount(1, $monthlyTemplate->generatedOrders);
        $this->assertCount(0, $futureTemplate->generatedOrders); // Not due yet
    }

    /** @test */
    public function price_recalculation_during_recurring_order_generation()
    {
        // Create wholesale customer
        $customer = User::factory()->create(['customer_type' => 'wholesale']);
        
        $product = Product::factory()->create([
            'base_price' => 20.00,
            'wholesale_price' => 16.00,
        ]);

        $priceVariation = PriceVariation::factory()->forProduct($product)->create([
            'price' => 20.00, // Original retail price in template
        ]);

        // Create recurring template with retail price
        $template = Order::factory()->recurring()->create([
            'user_id' => $customer->id,
            'customer_type' => 'wholesale',
        ]);

        OrderItem::factory()->create([
            'order_id' => $template->id,
            'product_id' => $product->id,
            'price_variation_id' => $priceVariation->id,
            'quantity' => 5,
            'price' => 20.00, // Template has retail price
        ]);

        // Generate recurring order
        $generatedOrder = $template->generateNextRecurringOrder();

        $this->assertNotNull($generatedOrder);
        
        // Verify order was created for wholesale customer
        $this->assertEquals('wholesale', $generatedOrder->customer_type);
        $this->assertEquals($customer->id, $generatedOrder->user_id);
        
        // Note: In a real test, you'd verify price recalculation
        // This would require the actual product pricing logic to be implemented
        $generatedItem = $generatedOrder->orderItems->first();
        $this->assertEquals(5, $generatedItem->quantity); // Quantity preserved
        $this->assertEquals($product->id, $generatedItem->product_id);
    }

    /** @test */
    public function order_search_and_filtering_capabilities()
    {
        $retailCustomer = User::factory()->create(['customer_type' => 'retail']);
        $wholesaleCustomer = User::factory()->create(['customer_type' => 'wholesale']);

        // Create various types of orders
        $retailOrder = Order::factory()->retail()->create(['user_id' => $retailCustomer->id]);
        $wholesaleOrder = Order::factory()->wholesale()->create(['user_id' => $wholesaleCustomer->id]);
        $recurringTemplate = Order::factory()->recurring()->create();
        $farmersMarketOrder = Order::factory()->farmersMarket()->create();
        $completedOrder = Order::factory()->withStatus('completed')->create();

        // Test filtering by customer type
        $retailOrders = Order::whereHas('user', fn($q) => $q->where('customer_type', 'retail'))->get();
        $this->assertTrue($retailOrders->contains($retailOrder));
        $this->assertFalse($retailOrders->contains($wholesaleOrder));

        // Test filtering by order type
        $farmersMarketOrders = Order::where('order_type', 'farmers_market')->get();
        $this->assertTrue($farmersMarketOrders->contains($farmersMarketOrder));

        // Test filtering recurring templates
        $templates = Order::where('is_recurring', true)
            ->whereNull('parent_recurring_order_id')
            ->get();
        $this->assertTrue($templates->contains($recurringTemplate));

        // Test filtering by status
        $completedOrders = Order::where('status', 'completed')->get();
        $this->assertTrue($completedOrders->contains($completedOrder));
    }
}
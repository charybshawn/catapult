<?php

namespace Tests\TestHelpers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use App\Models\Invoice;
use Carbon\Carbon;

/**
 * Helper methods for order-related tests
 */
trait OrderTestHelpers
{
    /**
     * Create a complete order with customer, products, and items
     */
    protected function createCompleteOrder(array $orderAttributes = [], array $itemsData = []): Order
    {
        $customer = User::factory()->create($orderAttributes['customer'] ?? []);
        
        $order = Order::factory()->create(array_merge([
            'user_id' => $customer->id,
        ], $orderAttributes));

        // Create default items if none specified
        if (empty($itemsData)) {
            $itemsData = [
                ['quantity' => 2, 'price' => 15.00],
                ['quantity' => 1, 'price' => 8.50],
            ];
        }

        foreach ($itemsData as $itemData) {
            $product = Product::factory()->create($itemData['product'] ?? []);
            $priceVariation = PriceVariation::factory()->forProduct($product)->create([
                'price' => $itemData['price'],
            ]);

            OrderItem::factory()->create(array_merge([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
            ], $itemData));
        }

        return $order->fresh(['orderItems', 'user']);
    }

    /**
     * Create a recurring order template with items
     */
    protected function createRecurringTemplate(array $attributes = [], array $itemsData = []): Order
    {
        $template = $this->createCompleteOrder(
            array_merge(['is_recurring' => true, 'status' => 'template'], $attributes),
            $itemsData
        );

        return $template;
    }

    /**
     * Create a complete B2B order setup
     */
    protected function createB2BOrder(array $attributes = []): Order
    {
        $wholesaleCustomer = User::factory()->create([
            'customer_type' => 'wholesale',
        ]);

        return $this->createCompleteOrder(array_merge([
            'customer' => ['customer_type' => 'wholesale'],
            'user_id' => $wholesaleCustomer->id,
            'order_type' => 'b2b',
            'billing_frequency' => 'monthly',
            'customer_type' => 'wholesale',
        ], $attributes));
    }

    /**
     * Create orders for consolidated billing test scenarios
     */
    protected function createConsolidatedBillingScenario(int $orderCount = 3): array
    {
        $customer = User::factory()->create(['customer_type' => 'wholesale']);
        $billingStart = Carbon::parse('2025-01-01');
        $billingEnd = Carbon::parse('2025-01-31');

        $orders = [];
        for ($i = 1; $i <= $orderCount; $i++) {
            $order = $this->createCompleteOrder([
                'user_id' => $customer->id,
                'order_type' => 'b2b',
                'billing_frequency' => 'monthly',
                'customer_type' => 'wholesale',
                'delivery_date' => $billingStart->copy()->addDays($i * 5),
                'billing_period_start' => $billingStart,
                'billing_period_end' => $billingEnd,
            ], [
                ['quantity' => $i + 1, 'price' => 20.00],
            ]);

            $orders[] = $order;
        }

        return [
            'customer' => $customer,
            'orders' => $orders,
            'billing_start' => $billingStart,
            'billing_end' => $billingEnd,
            'total_amount' => collect($orders)->sum(fn($order) => $order->totalAmount()),
        ];
    }

    /**
     * Create invoice for order with proper relationships
     */
    protected function createInvoiceForOrder(Order $order, array $attributes = []): Invoice
    {
        return Invoice::factory()->forOrder($order)->create(array_merge([
            'amount' => $order->totalAmount(),
            'total_amount' => $order->totalAmount(),
        ], $attributes));
    }

    /**
     * Simulate full order payment
     */
    protected function payOrder(Order $order, string $status = 'completed'): void
    {
        $order->payments()->create([
            'amount' => $order->totalAmount(),
            'status' => $status,
        ]);
    }

    /**
     * Simulate partial order payment
     */
    protected function payOrderPartially(Order $order, float $amount, string $status = 'completed'): void
    {
        $order->payments()->create([
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    /**
     * Progress order through standard workflow stages
     */
    protected function progressOrderThroughWorkflow(Order $order): Order
    {
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

        return $order->fresh();
    }

    /**
     * Assert order has expected financial state
     */
    protected function assertOrderFinancialState(Order $order, float $expectedTotal, float $expectedBalance = null, bool $isPaid = null): void
    {
        $this->assertEquals($expectedTotal, $order->totalAmount(), "Order total amount mismatch");
        
        if ($expectedBalance !== null) {
            $this->assertEquals($expectedBalance, $order->remainingBalance(), "Order remaining balance mismatch");
        }
        
        if ($isPaid !== null) {
            $this->assertEquals($isPaid, $order->isPaid(), "Order payment status mismatch");
        }
    }

    /**
     * Assert recurring order template properties
     */
    protected function assertRecurringTemplate(Order $order, string $frequency, bool $isActive = true): void
    {
        $this->assertTrue($order->is_recurring, "Order should be recurring");
        $this->assertTrue($order->isRecurringTemplate(), "Order should be a recurring template");
        $this->assertEquals($frequency, $order->recurring_frequency, "Recurring frequency mismatch");
        $this->assertEquals($isActive, $order->is_recurring_active, "Recurring active state mismatch");
        $this->assertEquals('template', $order->status, "Template status mismatch");
    }

    /**
     * Assert generated order properties
     */
    protected function assertGeneratedOrder(Order $order, Order $template): void
    {
        $this->assertTrue($order->isGeneratedFromRecurring(), "Order should be generated from recurring");
        $this->assertEquals($template->id, $order->parent_recurring_order_id, "Parent template ID mismatch");
        $this->assertEquals($template->user_id, $order->user_id, "Customer mismatch");
        $this->assertFalse($order->is_recurring, "Generated order should not be recurring");
        $this->assertEquals('pending', $order->status, "Generated order should be pending");
    }

    /**
     * Assert order items were copied correctly
     */
    protected function assertOrderItemsCopied(Order $sourceOrder, Order $targetOrder): void
    {
        $this->assertEquals(
            $sourceOrder->orderItems->count(),
            $targetOrder->orderItems->count(),
            "Order items count mismatch"
        );

        foreach ($sourceOrder->orderItems as $index => $sourceItem) {
            $targetItem = $targetOrder->orderItems->get($index);
            
            $this->assertEquals($sourceItem->product_id, $targetItem->product_id, "Product ID mismatch");
            $this->assertEquals($sourceItem->quantity, $targetItem->quantity, "Quantity mismatch");
            $this->assertEquals($sourceItem->price_variation_id, $targetItem->price_variation_id, "Price variation ID mismatch");
        }
    }

    /**
     * Create test data for different customer types
     */
    protected function createCustomerTypeTestData(): array
    {
        return [
            'retail_customer' => User::factory()->create(['customer_type' => 'retail']),
            'wholesale_customer' => User::factory()->create(['customer_type' => 'wholesale']),
            'retail_product_price' => 15.00,
            'wholesale_product_price' => 12.00,
        ];
    }

    /**
     * Mock time for recurring order testing
     */
    protected function travelToDate(string $date): void
    {
        Carbon::setTestNow(Carbon::parse($date));
    }

    /**
     * Reset time mocking
     */
    protected function resetTime(): void
    {
        Carbon::setTestNow();
    }

    /**
     * Create multiple orders for testing filters and queries
     */
    protected function createOrdersForFiltering(): array
    {
        return [
            'retail_order' => $this->createCompleteOrder(['customer' => ['customer_type' => 'retail']]),
            'wholesale_order' => $this->createCompleteOrder(['customer' => ['customer_type' => 'wholesale']]),
            'farmers_market_order' => $this->createCompleteOrder(['order_type' => 'farmers_market']),
            'b2b_order' => $this->createB2BOrder(),
            'recurring_template' => $this->createRecurringTemplate(),
            'completed_order' => $this->createCompleteOrder(['status' => 'completed']),
            'cancelled_order' => $this->createCompleteOrder(['status' => 'cancelled']),
        ];
    }
}
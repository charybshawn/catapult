<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use App\Models\Invoice;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FactoryTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_order_with_factory()
    {
        $order = Order::factory()->create();

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotNull($order->user_id);
        $this->assertNotNull($order->harvest_date);
        $this->assertNotNull($order->delivery_date);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_recurring_order_with_factory()
    {
        $order = Order::factory()->recurring()->create();

        $this->assertTrue($order->is_recurring);
        $this->assertTrue($order->is_recurring_active);
        $this->assertEquals('template', $order->status->code);
        $this->assertNotNull($order->recurring_frequency);
        $this->assertTrue($order->isRecurringTemplate());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_user_with_factory()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_product_manually()
    {
        // Create product manually to avoid factory issues
        $category = \App\Models\Category::factory()->create();
        
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test description',
            'active' => true,
            'is_visible_in_store' => true,
            'category_id' => $category->id,
            'stock_status_id' => \App\Models\ProductStockStatus::firstOrCreate(['code' => 'in_stock'], ['name' => 'In Stock', 'description' => 'Product is in stock'])->id,
            'base_price' => 10.00,
            'wholesale_price' => 8.00,
            'bulk_price' => 6.00,
            'special_price' => 5.00,
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals('Test Product', $product->name);
        $this->assertTrue($product->active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_price_variation_manually()
    {
        $category = \App\Models\Category::factory()->create();
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test description',
            'active' => true,
            'is_visible_in_store' => true,
            'category_id' => $category->id,
            'stock_status_id' => \App\Models\ProductStockStatus::firstOrCreate(['code' => 'in_stock'], ['name' => 'In Stock', 'description' => 'Product is in stock'])->id,
            'base_price' => 10.00,
        ]);
        
        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => '4oz Container',
            'sku' => 'TEST001',
            'price' => 12.50,
            'is_default' => true,
            'is_global' => false,
            'is_active' => true,
            'fill_weight_grams' => 113.4,
        ]);

        $this->assertInstanceOf(PriceVariation::class, $priceVariation);
        $this->assertEquals($product->id, $priceVariation->product_id);
        $this->assertEquals('4oz Container', $priceVariation->name);
        $this->assertEquals(12.50, $priceVariation->price);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_order_item_manually()
    {
        $order = Order::factory()->create();
        $category = \App\Models\Category::factory()->create();
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test description',
            'active' => true,
            'is_visible_in_store' => true,
            'category_id' => $category->id,
            'stock_status_id' => \App\Models\ProductStockStatus::firstOrCreate(['code' => 'in_stock'], ['name' => 'In Stock', 'description' => 'Product is in stock'])->id,
            'base_price' => 10.00,
        ]);
        
        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => '4oz Container',
            'sku' => 'TEST001',
            'price' => 12.50,
            'is_default' => true,
            'is_global' => false,
            'is_active' => true,
            'fill_weight_grams' => 113.4,
        ]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price_variation_id' => $priceVariation->id,
            'quantity' => 3,
            'price' => 12.50,
        ]);

        $this->assertInstanceOf(OrderItem::class, $orderItem);
        $this->assertEquals(3, $orderItem->quantity);
        $this->assertEquals(12.50, $orderItem->price);
        $this->assertEquals(37.50, $orderItem->subtotal());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_create_invoice_with_factory()
    {
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->forOrder($order)->create();

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($order->id, $invoice->order_id);
        $this->assertEquals($order->user_id, $invoice->order->user_id);
        $this->assertNotNull($invoice->invoice_number);
    }
}
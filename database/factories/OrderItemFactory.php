<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\PriceVariation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'price_variation_id' => null, // Will be set in afterCreating
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->randomFloat(2, 1, 50),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (OrderItem $orderItem) {
            // Ensure we have a price variation
            if (!$orderItem->price_variation_id && $orderItem->product) {
                // Try to get existing default price variation
                $priceVariation = $orderItem->product->priceVariations()->where('is_default', true)->first();
                
                if (!$priceVariation) {
                    // Create a default price variation using the current order item price
                    $priceVariation = \App\Models\PriceVariation::create([
                        'product_id' => $orderItem->product->id,
                        'name' => 'Default',
                        'sku' => 'DEFAULT-' . $orderItem->product->id,
                        'price' => $orderItem->price,
                        'is_default' => true,
                        'is_global' => false,
                        'is_active' => true,
                        'fill_weight' => 113.4,
                    ]);
                }
                
                $orderItem->price_variation_id = $priceVariation->id;
                // Don't override explicitly set prices
                if (!$orderItem->isDirty('price')) {
                    $orderItem->price = $priceVariation->price;
                }
            }
        });
    }

    /**
     * Create an order item with a specific quantity.
     */
    public function withQuantity(int $quantity): static
    {
        return $this->state([
            'quantity' => $quantity,
        ]);
    }

    /**
     * Create an order item with a specific price.
     */
    public function withPrice(float $price): static
    {
        return $this->state([
            'price' => $price,
        ]);
    }

    /**
     * Create an order item for a bulk product.
     */
    public function bulk(): static
    {
        return $this->state([
            'quantity' => $this->faker->numberBetween(100, 1000), // grams
        ]);
    }

    /**
     * Create an order item for a retail customer.
     */
    public function retail(): static
    {
        return $this->state(function (array $attributes) {
            $product = Product::factory()->create();
            $retailPrice = $product->base_price ?? $this->faker->randomFloat(2, 5, 25);
            
            return [
                'product_id' => $product->id,
                'price' => $retailPrice,
                'quantity' => $this->faker->numberBetween(1, 5),
            ];
        });
    }

    /**
     * Create an order item for a wholesale customer.
     */
    public function wholesale(): static
    {
        return $this->state(function (array $attributes) {
            $product = Product::factory()->create();
            $wholesalePrice = $product->wholesale_price ?? ($product->base_price ?? 10) * 0.8;
            
            return [
                'product_id' => $product->id,
                'price' => $wholesalePrice,
                'quantity' => $this->faker->numberBetween(10, 50),
            ];
        });
    }

    /**
     * Create an order item with specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state([
            'product_id' => $product->id,
            'price' => $product->base_price ?? $this->faker->randomFloat(2, 1, 50),
        ]);
    }

    /**
     * Create an order item for specific order.
     */
    public function forOrder(Order $order): static
    {
        return $this->state([
            'order_id' => $order->id,
        ]);
    }
}
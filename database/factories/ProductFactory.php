<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductStockStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            // Create a default price variation for the product
            if ($product->priceVariations()->count() === 0) {
                \App\Models\PriceVariation::create([
                    'product_id' => $product->id,
                    'name' => 'Default',
                    'sku' => 'DEFAULT-' . $product->id,
                    'price' => $product->base_price ?? 10.00,
                    'is_default' => true,
                    'is_global' => false,
                    'is_active' => true,
                    'fill_weight' => 113.4,
                ]);
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true) . ' ' . $this->faker->randomNumber(3),
            'description' => $this->faker->sentence,
            'active' => true,
            'is_visible_in_store' => true,
            'category_id' => function () {
                return Category::factory()->create()->id;
            },
            'stock_status_id' => ProductStockStatus::inRandomOrder()->first()?->id ?? ProductStockStatus::firstOrCreate(['code' => 'in_stock'], ['name' => 'In Stock', 'description' => 'Product is in stock'])->id,
            'base_price' => $this->faker->randomFloat(2, 10, 100),
            'wholesale_price' => $this->faker->randomFloat(2, 5, 90),
            'bulk_price' => $this->faker->randomFloat(2, 3, 80), 
            'special_price' => $this->faker->randomFloat(2, 5, 50),
        ];
    }
    
    /**
     * Indicate that the product is inactive.
     *
     * @return $this
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
    
    /**
     * Indicate that the product is not visible in store.
     *
     * @return $this
     */
    public function notVisibleInStore(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible_in_store' => false,
        ]);
    }
} 
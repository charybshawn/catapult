<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
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
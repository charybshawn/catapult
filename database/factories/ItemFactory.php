<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'active' => true,
            'is_visible_in_store' => true,
        ];
    }
    
    /**
     * Indicate that the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
    
    /**
     * Indicate that the item is not visible in store.
     */
    public function notVisibleInStore(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible_in_store' => false,
        ]);
    }
}
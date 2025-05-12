<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'category_id' => Category::factory(),
            'active' => true,
            'is_visible_in_store' => true,
            'base_price' => $this->faker->randomFloat(2, 10, 100),
            'wholesale_price' => $this->faker->randomFloat(2, 5, 80),
            'bulk_price' => $this->faker->randomFloat(2, 3, 60),
            'special_price' => $this->faker->randomFloat(2, 7, 90),
        ];
    }
} 
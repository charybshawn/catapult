<?php

namespace Database\Factories;

use App\Models\ConsumableUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumableUnit>
 */
class ConsumableUnitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConsumableUnit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['weight', 'volume', 'count', 'length'];
        $category = $this->faker->randomElement($categories);
        
        return [
            'code' => $this->faker->unique()->slug(1),
            'name' => $this->faker->words(2, true),
            'symbol' => $this->faker->lexify('???'),
            'description' => $this->faker->sentence(),
            'category' => $category,
            'conversion_factor' => $this->faker->randomFloat(2, 0.1, 1000),
            'base_unit' => $this->faker->boolean(20), // 20% chance of being base unit
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Create a gram unit.
     */
    public function grams(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'g',
            'name' => 'Grams',
            'symbol' => 'g',
            'category' => 'weight',
            'conversion_factor' => 1.0,
            'base_unit' => true,
        ]);
    }

    /**
     * Create a kilogram unit.
     */
    public function kilograms(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'kg',
            'name' => 'Kilograms',
            'symbol' => 'kg',
            'category' => 'weight',
            'conversion_factor' => 1000.0,
            'base_unit' => false,
        ]);
    }

    /**
     * Create a unit unit.
     */
    public function unit(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'unit',
            'name' => 'Unit',
            'symbol' => 'unit',
            'category' => 'count',
            'conversion_factor' => 1.0,
            'base_unit' => true,
        ]);
    }

    /**
     * Create inactive unit.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
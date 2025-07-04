<?php

namespace Database\Factories;

use App\Models\ConsumableType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsumableType>
 */
class ConsumableTypeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConsumableType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['red', 'blue', 'green', 'yellow', 'purple', 'orange', 'pink', 'gray'];
        
        return [
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->randomElement($colors),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Create a seed type.
     */
    public function seed(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'seed',
            'name' => 'Seeds',
            'description' => 'Seed consumables',
            'color' => 'green',
        ]);
    }

    /**
     * Create a packaging type.
     */
    public function packaging(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'packaging',
            'name' => 'Packaging',
            'description' => 'Packaging materials',
            'color' => 'brown',
        ]);
    }

    /**
     * Create inactive type.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
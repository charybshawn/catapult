<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConsumableTransaction>
 */
class ConsumableTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(3, 1, 1000);
        $isOutbound = $this->faker->boolean();
        
        return [
            'consumable_id' => \App\Models\Consumable::factory(),
            'type' => $this->faker->randomElement([
                \App\Models\ConsumableTransaction::TYPE_CONSUMPTION,
                \App\Models\ConsumableTransaction::TYPE_ADDITION,
                \App\Models\ConsumableTransaction::TYPE_ADJUSTMENT,
                \App\Models\ConsumableTransaction::TYPE_WASTE,
                \App\Models\ConsumableTransaction::TYPE_INITIAL,
            ]),
            'quantity' => $isOutbound ? -$quantity : $quantity,
            'balance_after' => $this->faker->randomFloat(3, 0, 10000),
            'user_id' => \App\Models\User::factory(),
            'reference_type' => $this->faker->optional()->randomElement(['crop', 'order', 'recipe']),
            'reference_id' => $this->faker->optional()->numberBetween(1, 100),
            'notes' => $this->faker->optional()->sentence(),
            'metadata' => $this->faker->optional()->passthrough([
                'source' => $this->faker->word(),
                'lot_number' => $this->faker->word(),
            ]),
        ];
    }

    /**
     * Create a consumption transaction.
     */
    public function consumption(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \App\Models\ConsumableTransaction::TYPE_CONSUMPTION,
            'quantity' => -abs($this->faker->randomFloat(3, 1, 100)),
        ]);
    }

    /**
     * Create an addition transaction.
     */
    public function addition(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \App\Models\ConsumableTransaction::TYPE_ADDITION,
            'quantity' => abs($this->faker->randomFloat(3, 1, 1000)),
        ]);
    }

    /**
     * Create an initial stock transaction.
     */
    public function initial(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => \App\Models\ConsumableTransaction::TYPE_INITIAL,
            'quantity' => abs($this->faker->randomFloat(3, 100, 1000)),
            'user_id' => null,
            'notes' => 'Initial stock',
        ]);
    }
}

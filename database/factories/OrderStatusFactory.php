<?php

namespace Database\Factories;

use App\Models\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderStatus>
 */
class OrderStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stages = ['pre_production', 'production', 'fulfillment', 'final'];
        $colors = ['gray', 'yellow', 'blue', 'green', 'lime', 'emerald', 'indigo', 'purple', 'violet', 'red'];
        
        return [
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->randomElement($colors),
            'badge_color' => $this->faker->optional()->randomElement($colors),
            'stage' => $this->faker->randomElement($stages),
            'requires_crops' => $this->faker->boolean(30),
            'is_active' => $this->faker->boolean(90),
            'is_final' => $this->faker->boolean(20),
            'allows_modifications' => $this->faker->boolean(70),
            'sort_order' => $this->faker->numberBetween(10, 200),
        ];
    }

    /**
     * Indicate that the status is in pre-production stage.
     */
    public function preProduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => 'pre_production',
            'requires_crops' => false,
            'allows_modifications' => true,
        ]);
    }

    /**
     * Indicate that the status is in production stage.
     */
    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => 'production',
            'requires_crops' => true,
            'allows_modifications' => false,
        ]);
    }

    /**
     * Indicate that the status is in fulfillment stage.
     */
    public function fulfillment(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => 'fulfillment',
            'requires_crops' => false,
            'allows_modifications' => false,
        ]);
    }

    /**
     * Indicate that the status is final.
     */
    public function final(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => 'final',
            'is_final' => true,
            'allows_modifications' => false,
        ]);
    }

    /**
     * Indicate that the status is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the status is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
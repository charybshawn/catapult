<?php

namespace Database\Factories;

use App\Models\CropPlanStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CropPlanStatus>
 */
class CropPlanStatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CropPlanStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['gray', 'yellow', 'blue', 'green', 'lime', 'emerald', 'indigo', 'purple', 'violet', 'red'];
        
        return [
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->randomElement($colors),
            'is_active' => $this->faker->boolean(90),
            'sort_order' => $this->faker->numberBetween(10, 200),
        ];
    }

    /**
     * Indicate that the status is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'draft',
            'name' => 'Draft',
            'description' => 'Crop plan is in draft status',
            'color' => 'gray',
            'is_active' => true,
            'sort_order' => 10,
        ]);
    }

    /**
     * Indicate that the status is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'active',
            'name' => 'Active',
            'description' => 'Crop plan is approved and active',
            'color' => 'blue',
            'is_active' => true,
            'sort_order' => 20,
        ]);
    }

    /**
     * Indicate that the status is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'completed',
            'name' => 'Completed',
            'description' => 'Crop plan has been completed',
            'color' => 'green',
            'is_active' => true,
            'sort_order' => 30,
        ]);
    }

    /**
     * Indicate that the status is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'cancelled',
            'name' => 'Cancelled',
            'description' => 'Crop plan has been cancelled',
            'color' => 'red',
            'is_active' => true,
            'sort_order' => 40,
        ]);
    }
}
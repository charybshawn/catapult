<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Crop>
 */
class CropFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Crop::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $germinationAt = Carbon::instance($this->faker->dateTimeBetween('-2 weeks', 'now'));
        
        return [
            'recipe_id' => Recipe::factory(), // Associate with a recipe
            'tray_number' => 'T-' . $this->faker->unique()->numberBetween(100, 999),
            'tray_count' => $this->faker->numberBetween(1, 10), // Add tray count for tests
            // Note: current_stage_id will be set automatically by CropStageCalculator
            'germination_at' => $germinationAt, // Germination timestamp
            'blackout_at' => null,
            'light_at' => null,
            'harvested_at' => null,
            'watering_suspended_at' => null,
            'notes' => $this->faker->optional()->paragraph,
            'requires_soaking' => false,
        ];
    }
    
    /**
     * Indicate that the crop is for a specific recipe.
     */
    public function forRecipe(Recipe $recipe): static
    {
        return $this->state(fn (array $attributes) => [
            'recipe_id' => $recipe->id,
        ]);
    }
    
    /**
     * Indicate that the crop is in the germination stage.
     */
    public function germination(): static
    {
        return $this->state(function (array $attributes) {
            $germinationAt = fake()->dateTimeBetween('-6 days', 'now');
            
            return [
                'germination_at' => $germinationAt,
                'blackout_at' => null,
                'light_at' => null,
                'harvested_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that the crop is in the blackout stage.
     */
    public function blackout(): static
    {
        return $this->state(function (array $attributes) {
            $germinationAt = fake()->dateTimeBetween('-8 days', '-5 days');
            $blackoutAt = fake()->dateTimeBetween($germinationAt, 'now');
            
            return [
                'germination_at' => $germinationAt,
                'blackout_at' => $blackoutAt,
                'light_at' => null,
                'harvested_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that the crop is in the light stage.
     */
    public function light(): static
    {
        return $this->state(function (array $attributes) {
            $germinationAt = fake()->dateTimeBetween('-10 days', '-6 days');
            $blackoutAt = fake()->dateTimeBetween($germinationAt, '-4 days');
            $lightAt = fake()->dateTimeBetween($blackoutAt, 'now');
            
            return [
                'germination_at' => $germinationAt,
                'blackout_at' => $blackoutAt,
                'light_at' => $lightAt,
                'harvested_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that the crop is harvested.
     */
    public function harvested(): static
    {
        return $this->state(function (array $attributes) {
            $germinationAt = fake()->dateTimeBetween('-15 days', '-10 days');
            $blackoutAt = fake()->dateTimeBetween($germinationAt, '-8 days');
            $lightAt = fake()->dateTimeBetween($blackoutAt, '-3 days');
            $harvestedAt = fake()->dateTimeBetween($lightAt, 'now');
            
            return [
                'germination_at' => $germinationAt,
                'blackout_at' => $blackoutAt,
                'light_at' => $lightAt,
                'harvested_at' => $harvestedAt,
            ];
        });
    }
    
    /**
     * Indicate that the crop requires soaking.
     */
    public function soaking(): static
    {
        return $this->state(function (array $attributes) {
            $soakingAt = fake()->dateTimeBetween('-1 day', 'now');
            
            return [
                'requires_soaking' => true,
                'soaking_at' => $soakingAt,
                'germination_at' => null,
                'blackout_at' => null,
                'light_at' => null,
                'harvested_at' => null,
            ];
        });
    }
    
    /**
     * Indicate that watering is suspended.
     */
    public function wateringSuspended(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'watering_suspended_at' => fake()->dateTimeBetween('-3 days', 'now'),
            ];
        });
    }
}
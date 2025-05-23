<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $plantedAt = Carbon::instance($this->faker->dateTimeBetween('-2 weeks', 'now'));
        
        return [
            'recipe_id' => Recipe::factory(), // Associate with a recipe
            'tray_number' => 'T-' . $this->faker->unique()->numberBetween(100, 999),
            'planted_at' => $plantedAt,
            'planting_at' => $plantedAt, // Set planting timestamp
            // Set current_stage to a valid enum value
            'current_stage' => 'germination', // Default to germination
            'germination_at' => $plantedAt, // Assume germination starts at planting
            'blackout_at' => null,
            'light_at' => null,
            'harvested_at' => null,
            'harvest_weight_grams' => null,
            'watering_suspended_at' => null,
            'notes' => $this->faker->optional()->paragraph,
            // Default time/status fields (match defaults in CreateCrop)
            'time_to_next_stage_minutes' => 0,
            'time_to_next_stage_status' => 'Calculating...',
            'stage_age_minutes' => 0,
            'stage_age_status' => '0m',
            'total_age_minutes' => 0,
            'total_age_status' => '0m',
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
     * Indicate that the crop is in the planting stage.
     */
    public function planting(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 'planting',
            'planting_at' => $attributes['planted_at'] ?? now(),
            'germination_at' => null,
            'blackout_at' => null,
            'light_at' => null,
            'harvested_at' => null,
            'harvest_weight_grams' => null,
        ]);
    }
    
    /**
     * Indicate that the crop is in the germination stage.
     */
    public function germination(): static
    {
        return $this->state(function (array $attributes) {
            $plantedAt = $attributes['planted_at'] ?? fake()->dateTimeBetween('-30 days', '-7 days');
            $germinationAt = fake()->dateTimeBetween($plantedAt, 'now');
            
            return [
                'current_stage' => 'germination',
                'planting_at' => $plantedAt,
                'germination_at' => $germinationAt,
                'blackout_at' => null,
                'light_at' => null,
                'harvested_at' => null,
                'harvest_weight_grams' => null,
            ];
        });
    }
    
    /**
     * Indicate that the crop is in the blackout stage.
     */
    public function blackout(): static
    {
        return $this->state(function (array $attributes) {
            $plantedAt = $attributes['planted_at'] ?? fake()->dateTimeBetween('-30 days', '-7 days');
            $germinationAt = fake()->dateTimeBetween($plantedAt, '-5 days');
            $blackoutAt = fake()->dateTimeBetween($germinationAt, 'now');
            
            return [
                'current_stage' => 'blackout',
                'planting_at' => $plantedAt,
                'germination_at' => $germinationAt,
                'blackout_at' => $blackoutAt,
                'light_at' => null,
                'harvested_at' => null,
                'harvest_weight_grams' => null,
            ];
        });
    }
    
    /**
     * Indicate that the crop is in the light stage.
     */
    public function light(): static
    {
        return $this->state(function (array $attributes) {
            $plantedAt = $attributes['planted_at'] ?? fake()->dateTimeBetween('-30 days', '-7 days');
            $germinationAt = fake()->dateTimeBetween($plantedAt, '-6 days');
            $blackoutAt = fake()->dateTimeBetween($germinationAt, '-4 days');
            $lightAt = fake()->dateTimeBetween($blackoutAt, 'now');
            
            return [
                'current_stage' => 'light',
                'planting_at' => $plantedAt,
                'germination_at' => $germinationAt,
                'blackout_at' => $blackoutAt,
                'light_at' => $lightAt,
                'harvested_at' => null,
                'harvest_weight_grams' => null,
            ];
        });
    }
    
    /**
     * Indicate that the crop is harvested.
     */
    public function harvested(): static
    {
        return $this->state(function (array $attributes) {
            $plantedAt = $attributes['planted_at'] ?? fake()->dateTimeBetween('-30 days', '-7 days');
            $germinationAt = fake()->dateTimeBetween($plantedAt, '-6 days');
            $blackoutAt = fake()->dateTimeBetween($germinationAt, '-5 days');
            $lightAt = fake()->dateTimeBetween($blackoutAt, '-2 days');
            $harvestedAt = fake()->dateTimeBetween($lightAt, 'now');
            
            return [
                'current_stage' => 'harvested',
                'planting_at' => $plantedAt,
                'germination_at' => $germinationAt,
                'blackout_at' => $blackoutAt,
                'light_at' => $lightAt,
                'harvested_at' => $harvestedAt,
                'harvest_weight_grams' => fake()->randomFloat(2, 50, 500),
            ];
        });
    }
    
    /**
     * Indicate that watering is suspended.
     */
    public function wateringSuspended(): static
    {
        return $this->state(function (array $attributes) {
            $plantedAt = $attributes['planted_at'] ?? fake()->dateTimeBetween('-30 days', '-7 days');
            
            return [
                'watering_suspended_at' => fake()->dateTimeBetween($plantedAt, 'now'),
            ];
        });
    }
} 
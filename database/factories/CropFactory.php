<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\Recipe;
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
        $recipe = Recipe::factory()->create();
        $plantedAt = fake()->dateTimeBetween('-30 days', 'now');
        $stageUpdatedAt = fake()->dateTimeBetween($plantedAt, 'now');
        
        $stages = ['planting', 'germination', 'blackout', 'light', 'harvested'];
        $currentStage = fake()->randomElement($stages);
        
        return [
            'recipe_id' => $recipe->id,
            'tray_number' => 'T-' . fake()->unique()->numberBetween(1, 999),
            'planted_at' => $plantedAt,
            'current_stage' => $currentStage,
            'stage_updated_at' => $stageUpdatedAt,
            'harvest_weight_grams' => $currentStage === 'harvested' ? fake()->randomFloat(2, 50, 500) : null,
            'watering_suspended_at' => fake()->optional(0.2)->dateTimeBetween($plantedAt, 'now'),
            'notes' => fake()->optional(0.7)->paragraph(),
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
            'harvest_weight_grams' => null,
        ]);
    }
    
    /**
     * Indicate that the crop is in the germination stage.
     */
    public function germination(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 'germination',
            'harvest_weight_grams' => null,
        ]);
    }
    
    /**
     * Indicate that the crop is in the blackout stage.
     */
    public function blackout(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 'blackout',
            'harvest_weight_grams' => null,
        ]);
    }
    
    /**
     * Indicate that the crop is in the light stage.
     */
    public function light(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stage' => 'light',
            'harvest_weight_grams' => null,
        ]);
    }
    
    /**
     * Indicate that the crop is harvested.
     */
    public function harvested(): static
    {
        return $this->state(function (array $attributes) {
            $plantedAt = $attributes['planted_at'] ?? fake()->dateTimeBetween('-30 days', '-7 days');
            $stageUpdatedAt = fake()->dateTimeBetween($plantedAt, 'now');
            
            return [
                'current_stage' => 'harvested',
                'stage_updated_at' => $stageUpdatedAt,
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
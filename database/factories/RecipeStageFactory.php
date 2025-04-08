<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeStage>
 */
class RecipeStageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RecipeStage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stageNames = ['Soak', 'Germination', 'Blackout', 'Light', 'Harvest'];
        $stageDays = [1, 2, 3, 7, 1];
        
        $stageIndex = fake()->numberBetween(0, count($stageNames) - 1);
        
        return [
            'recipe_id' => Recipe::factory(),
            'name' => $stageNames[$stageIndex],
            'day' => fake()->numberBetween(1, 14),
            'duration_days' => $stageDays[$stageIndex],
            'description' => fake()->sentence(),
            'instructions' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
    
    /**
     * Indicate that the recipe stage is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    /**
     * Indicate that the recipe stage is for a specific recipe.
     */
    public function forRecipe(Recipe $recipe): static
    {
        return $this->state(fn (array $attributes) => [
            'recipe_id' => $recipe->id,
        ]);
    }
    
    /**
     * Create a soak stage.
     */
    public function soak(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Soak',
            'day' => 1,
            'duration_days' => 1,
            'description' => 'Soak seeds in water',
            'instructions' => 'Soak seeds in clean water for 8-12 hours to initiate germination.',
        ]);
    }
    
    /**
     * Create a germination stage.
     */
    public function germination(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Germination',
            'day' => 2,
            'duration_days' => 2,
            'description' => 'Allow seeds to germinate',
            'instructions' => 'Keep seeds moist in a dark environment to encourage germination.',
        ]);
    }
    
    /**
     * Create a blackout stage.
     */
    public function blackout(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Blackout',
            'day' => 4,
            'duration_days' => 3,
            'description' => 'Keep trays in blackout conditions',
            'instructions' => 'Stack trays with weight on top and keep in dark conditions to encourage root development.',
        ]);
    }
    
    /**
     * Create a light stage.
     */
    public function light(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Light',
            'day' => 7,
            'duration_days' => 7,
            'description' => 'Expose to light for growth',
            'instructions' => 'Place trays under grow lights for 12-16 hours per day to encourage photosynthesis and growth.',
        ]);
    }
    
    /**
     * Create a harvest stage.
     */
    public function harvest(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Harvest',
            'day' => 14,
            'duration_days' => 1,
            'description' => 'Harvest microgreens',
            'instructions' => 'Cut microgreens just above the soil line, rinse, and package for sale or consumption.',
        ]);
    }
} 
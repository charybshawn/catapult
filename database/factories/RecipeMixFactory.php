<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeMix;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeMix>
 */
class RecipeMixFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RecipeMix::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'component_recipe_id' => Recipe::factory(),
            'percentage' => fake()->randomFloat(2, 10, 100),
        ];
    }
    
    /**
     * Indicate that the recipe mix is for a specific recipe.
     */
    public function forRecipe(Recipe $recipe): static
    {
        return $this->state(fn (array $attributes) => [
            'recipe_id' => $recipe->id,
        ]);
    }
    
    /**
     * Indicate that the recipe mix uses a specific component recipe.
     */
    public function withComponent(Recipe $componentRecipe): static
    {
        return $this->state(fn (array $attributes) => [
            'component_recipe_id' => $componentRecipe->id,
        ]);
    }
    
    /**
     * Set a specific percentage for the component.
     */
    public function withPercentage(float $percentage): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage' => $percentage,
        ]);
    }
} 
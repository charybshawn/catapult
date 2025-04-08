<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeWateringSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeWateringSchedule>
 */
class RecipeWateringScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RecipeWateringSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $wateringMethods = ['Bottom', 'Top', 'Mist', 'Soak'];
        
        return [
            'recipe_id' => Recipe::factory(),
            'day' => fake()->numberBetween(1, 14),
            'method' => fake()->randomElement($wateringMethods),
            'amount_ml' => fake()->randomElement([50, 100, 150, 200, 250]),
            'notes' => fake()->optional(0.6)->sentence(),
            'is_active' => true,
        ];
    }
    
    /**
     * Indicate that the watering schedule is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    /**
     * Indicate that the watering schedule is for a specific recipe.
     */
    public function forRecipe(Recipe $recipe): static
    {
        return $this->state(fn (array $attributes) => [
            'recipe_id' => $recipe->id,
        ]);
    }
    
    /**
     * Create a bottom watering schedule.
     */
    public function bottomWatering(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'Bottom',
            'amount_ml' => 200,
            'notes' => 'Water from the bottom of the tray to encourage root growth.',
        ]);
    }
    
    /**
     * Create a top watering schedule.
     */
    public function topWatering(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'Top',
            'amount_ml' => 150,
            'notes' => 'Water from the top using a gentle spray.',
        ]);
    }
    
    /**
     * Create a mist watering schedule.
     */
    public function mistWatering(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'Mist',
            'amount_ml' => 50,
            'notes' => 'Mist lightly to maintain humidity without overwatering.',
        ]);
    }
    
    /**
     * Create a soak watering schedule.
     */
    public function soakWatering(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'Soak',
            'amount_ml' => 250,
            'notes' => 'Soak seeds thoroughly to initiate germination.',
        ]);
    }
} 
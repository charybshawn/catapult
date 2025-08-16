<?php

namespace Database\Factories;

use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Recipe::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $commonNames = ['Sunflower', 'Radish', 'Pea', 'Broccoli', 'Arugula', 'Basil', 'Cilantro'];
        $cultivarNames = ['Black Oilseed', 'China Rose', 'Speckled', 'Waltham', 'Slow Bolt', 'Genovese', 'Coriander'];
        
        $commonName = fake()->randomElement($commonNames);
        $cultivarName = fake()->randomElement($cultivarNames);
        $name = $commonName . ' (' . $cultivarName . ') Recipe';
        
        return [
            'name' => $name,
            'common_name' => $commonName,
            'cultivar_name' => $cultivarName,
            'lot_number' => strtoupper(fake()->bothify('???##??')),
            'germination_days' => fake()->randomFloat(1, 1, 5),
            'blackout_days' => fake()->numberBetween(0, 5),
            'light_days' => fake()->randomFloat(1, 3, 12),
            'days_to_maturity' => fake()->randomFloat(1, 7, 30),
            'expected_yield_grams' => fake()->randomFloat(2, 50, 500),
            'buffer_percentage' => fake()->randomFloat(2, 10, 20),
            'seed_density_grams_per_tray' => fake()->randomFloat(2, 5, 50),
            'seed_soak_hours' => fake()->numberBetween(0, 24),
            'notes' => fake()->optional(0.7)->paragraph(),
            'is_active' => true,
        ];
    }
    
    /**
     * Indicate that the recipe is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    
    /**
     * Indicate that the recipe has a custom name.
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }
} 
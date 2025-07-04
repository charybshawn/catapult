<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\SeedEntry;
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
        $seedEntry = SeedEntry::first() ?? SeedEntry::factory()->create();
        $name = $seedEntry->common_name . ' Recipe';
        
        return [
            'name' => $name,
            'seed_entry_id' => $seedEntry->id,
            'seed_density' => fake()->randomFloat(2, 1, 10),
            'germination_days' => fake()->randomFloat(1, 1, 5),
            'blackout_days' => fake()->numberBetween(0, 5),
            'light_days' => fake()->randomFloat(1, 3, 12),
            'days_to_maturity' => fake()->randomFloat(1, 7, 30),
            'harvest_days' => fake()->numberBetween(7, 21),
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
     * Indicate that the recipe is for a specific seed variety.
     */
    public function forSeedVariety(SeedVariety $seedVariety): static
    {
        return $this->state(fn (array $attributes) => [
            'seed_variety_id' => $seedVariety->id,
            'name' => $seedVariety->name . ' Recipe',
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
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
        $seedEntry = SeedEntry::first() ?? SeedEntry::create([
            'supplier_id' => 1,
            'common_name' => fake()->randomElement(['Pea', 'Radish', 'Mustard', 'Broccoli', 'Kale']),
            'cultivar_name' => fake()->name() . ' Variety',
            'supplier_product_title' => fake()->words(3, true),
        ]);
        $name = $seedEntry->common_name . ' Recipe';
        
        return [
            'name' => $name,
            'seed_entry_id' => $seedEntry->id,
            'seed_density' => fake()->randomFloat(2, 1, 10),
            'blackout_days' => fake()->numberBetween(0, 5),
            'harvest_days' => fake()->numberBetween(7, 21),
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
<?php

namespace Database\Factories;

use App\Models\SeedVariety;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeedVariety>
 */
class SeedVarietyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SeedVariety::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $microgreens = [
            'Sunflower', 'Pea', 'Radish', 'Broccoli', 'Kale', 
            'Arugula', 'Mustard', 'Amaranth', 'Basil', 'Beet', 
            'Cabbage', 'Cilantro', 'Kohlrabi', 'Mizuna', 'Sorrel'
        ];
        
        $varieties = [
            'Black Oil', 'Grey Stripe', 'Speckled', 'Red', 'Purple', 
            'Green', 'Yellow', 'White', 'Daikon', 'Rambo', 
            'China Rose', 'Red Arrow', 'Calabrese', 'Di Cicco', 'Waltham'
        ];
        
        $microgreen = fake()->randomElement($microgreens);
        $variety = fake()->optional(0.7, '')->randomElement($varieties);
        $name = trim($microgreen . ' ' . $variety);
        
        return [
            'name' => $name,
            'crop_type' => $microgreen,
            'supplier_id' => Supplier::factory()->seed(),
            'cost_per_unit' => fake()->randomFloat(2, 5, 50),
            'unit_type' => fake()->randomElement(['lb', 'kg', 'oz', 'g']),
            'germination_rate' => fake()->numberBetween(70, 99),
            'days_to_maturity' => fake()->numberBetween(7, 21),
            'notes' => fake()->optional(0.6)->paragraph(),
            'is_active' => true,
        ];
    }
    
    /**
     * Indicate that the seed variety is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    /**
     * Indicate that the seed variety has a specific supplier.
     */
    public function forSupplier(Supplier $supplier): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => $supplier->id,
        ]);
    }
} 
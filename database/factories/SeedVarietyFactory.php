<?php

namespace Database\Factories;

use App\Models\SeedVariety;
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
} 
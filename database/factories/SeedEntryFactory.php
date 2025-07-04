<?php

namespace Database\Factories;

use App\Models\SeedEntry;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeedEntry>
 */
class SeedEntryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SeedEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $commonNames = ['Arugula', 'Basil', 'Broccoli', 'Kale', 'Lettuce', 'Spinach', 'Radish', 'Pea', 'Mustard'];
        $cultivars = ['Astro', 'Genovese', 'Red Russian', 'Buttercrunch', 'Space', 'Cherry Belle', 'Oregon Sugar Pod', 'Red Giant'];
        
        $commonName = fake()->randomElement($commonNames);
        $cultivar = fake()->randomElement($cultivars);
        
        return [
            'supplier_id' => Supplier::factory(),
            'common_name' => $commonName,
            'cultivar_name' => $cultivar,
            'supplier_product_title' => $commonName . ' (' . $cultivar . ') Seeds',
            'supplier_sku' => fake()->optional()->regexify('[A-Z]{2}[0-9]{4}'),
            'supplier_product_url' => fake()->url(),
            'url' => fake()->optional()->url(),
            'image_url' => fake()->optional()->imageUrl(),
            'description' => fake()->optional()->paragraph(),
            'tags' => fake()->optional()->words(3),
            'is_active' => true,
        ];
    }
    
    /**
     * Indicate that the seed entry is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    /**
     * Set specific variety name.
     */
    public function variety(string $commonName, string $cultivarName): static
    {
        return $this->state(fn (array $attributes) => [
            'common_name' => $commonName,
            'cultivar_name' => $cultivarName,
        ]);
    }
}
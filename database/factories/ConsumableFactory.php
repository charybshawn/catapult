<?php

namespace Database\Factories;

use App\Models\Consumable;
use App\Models\Supplier;
use App\Models\SeedCultivar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Consumable>
 */
class ConsumableFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consumable::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['packaging', 'label', 'other'];
        $type = fake()->randomElement($types);
        
        $name = match($type) {
            'packaging' => fake()->randomElement(['Clamshell Containers', 'Plastic Bags', 'Paper Towels', 'Boxes', 'Trays']),
            'label' => fake()->randomElement(['Product Labels', 'Price Tags', 'Stickers', 'Barcode Labels', 'Brand Labels']),
            'other' => fake()->randomElement(['Sanitizer', 'Hydrogen Peroxide', 'pH Test Strips', 'Scissors', 'Gloves', 'Pens']),
        };
        
        $unit = match($type) {
            'packaging' => fake()->randomElement(['pack', 'roll', 'box', 'case']),
            'label' => fake()->randomElement(['roll', 'sheet', 'pack']),
            'other' => fake()->randomElement(['bottle', 'pack', 'unit', 'piece']),
        };
        
        return [
            'name' => $name,
            'type' => $type,
            'supplier_id' => Supplier::factory()->consumable()->create()->id,
            'initial_stock' => fake()->numberBetween(1, 100),
            'consumed_quantity' => fake()->numberBetween(0, 50),
            'unit' => $unit,
            'restock_threshold' => fake()->numberBetween(5, 20),
            'restock_quantity' => fake()->numberBetween(10, 50),
            'cost_per_unit' => fake()->randomFloat(2, 1, 50),
            'notes' => fake()->optional(0.6)->paragraph(),
            'is_active' => true,
        ];
    }
    
    /**
     * Indicate that the consumable is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    /**
     * Indicate that the consumable is for packaging.
     */
    public function packaging(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'packaging',
            'name' => fake()->randomElement(['Clamshell Containers', 'Plastic Bags', 'Paper Towels', 'Boxes', 'Trays']),
            'unit' => fake()->randomElement(['pack', 'roll', 'box', 'case']),
        ]);
    }
    
    /**
     * Indicate that the consumable is for labels.
     */
    public function label(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'label',
            'name' => fake()->randomElement(['Product Labels', 'Price Tags', 'Stickers', 'Barcode Labels', 'Brand Labels']),
            'unit' => fake()->randomElement(['roll', 'sheet', 'pack']),
        ]);
    }
    
    /**
     * Indicate that the consumable is for seeds.
     */
    public function seed(): static
    {
        $seedVariety = SeedVariety::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'type' => 'seed',
            'seed_variety_id' => $seedVariety->id,
            'name' => $seedVariety->name,
            'unit' => fake()->randomElement(['g', 'kg', 'pack']),
            'quantity_per_unit' => fake()->randomFloat(2, 10, 1000),
            'quantity_unit' => 'g',
        ]);
    }
    
    /**
     * Indicate that the consumable is for soil.
     */
    public function soil(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'soil',
            'name' => fake()->randomElement(['Potting Mix', 'Seed Starting Mix', 'Coco Coir', 'Peat Moss', 'Vermiculite']),
            'unit' => fake()->randomElement(['bag', 'block', 'pack']),
            'quantity_per_unit' => fake()->randomFloat(2, 1, 20),
            'quantity_unit' => 'kg',
        ]);
    }
    
    /**
     * Indicate that the consumable is for other purposes.
     */
    public function other(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'other',
            'name' => fake()->randomElement(['Sanitizer', 'Hydrogen Peroxide', 'pH Test Strips', 'Scissors', 'Gloves', 'Pens']),
            'unit' => fake()->randomElement(['bottle', 'pack', 'unit', 'piece']),
        ]);
    }
    
    /**
     * Indicate that the consumable is for a specific supplier.
     */
    public function forSupplier(Supplier $supplier): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => $supplier->id,
        ]);
    }
    
    /**
     * Indicate that the consumable is for a specific seed variety.
     */
    public function forSeedVariety(SeedVariety $seedVariety): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'seed',
            'seed_variety_id' => $seedVariety->id,
            'name' => $seedVariety->name,
            'unit' => fake()->randomElement(['g', 'kg', 'pack']),
            'quantity_per_unit' => fake()->randomFloat(2, 10, 1000),
            'quantity_unit' => 'g',
        ]);
    }
    
    /**
     * Indicate that the consumable is low in stock.
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $restockThreshold = $attributes['restock_threshold'] ?? fake()->numberBetween(5, 20);
            
            return [
                'initial_stock' => fake()->numberBetween(0, $restockThreshold - 1),
            ];
        });
    }
} 
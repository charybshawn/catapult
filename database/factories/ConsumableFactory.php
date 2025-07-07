<?php

namespace Database\Factories;

use App\Models\Consumable;
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
        return [
            'name' => fake()->words(2, true),
            'consumable_type_id' => \App\Models\ConsumableType::factory(),
            'consumable_unit_id' => \App\Models\ConsumableUnit::factory(),
            'total_quantity' => fake()->numberBetween(100, 1000),
            'consumed_quantity' => fake()->numberBetween(0, 50),
            'quantity_unit' => fake()->randomElement(['g', 'kg', 'ml', 'L', 'unit']),
            'restock_threshold' => fake()->numberBetween(5, 20),
            'restock_quantity' => fake()->numberBetween(10, 50),
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
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(
                ['code' => 'packaging'],
                [
                    'name' => 'Packaging',
                    'description' => 'Packaging materials',
                    'color' => 'brown',
                    'is_active' => true,
                    'sort_order' => 2,
                ]
            )->id,
            'name' => fake()->randomElement(['Clamshell Containers', 'Plastic Bags', 'Paper Towels', 'Boxes', 'Trays']),
        ]);
    }
    
    /**
     * Indicate that the consumable is for labels.
     */
    public function label(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(
                ['code' => 'label'],
                [
                    'name' => 'Labels',
                    'description' => 'Labels and tags',
                    'color' => 'blue',
                    'is_active' => true,
                    'sort_order' => 4,
                ]
            )->id,
            'name' => fake()->randomElement(['Product Labels', 'Price Tags', 'Stickers', 'Barcode Labels', 'Brand Labels']),
        ]);
    }
    
    /**
     * Indicate that the consumable is for seeds.
     */
    public function seed(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(
                ['code' => 'seed'],
                [
                    'name' => 'Seeds',
                    'description' => 'Seed consumables',
                    'color' => 'green',
                    'is_active' => true,
                    'sort_order' => 1,
                ]
            )->id,
            'name' => fake()->words(2, true) . ' Seeds',
            'quantity_unit' => 'g',
        ]);
    }
    
    /**
     * Indicate that the consumable is for soil.
     */
    public function soil(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(
                ['code' => 'soil'],
                [
                    'name' => 'Soil',
                    'description' => 'Soil and growing media',
                    'color' => 'brown',
                    'is_active' => true,
                    'sort_order' => 3,
                ]
            )->id,
            'name' => fake()->randomElement(['Potting Mix', 'Seed Starting Mix', 'Coco Coir', 'Peat Moss', 'Vermiculite']),
            'quantity_unit' => 'kg',
        ]);
    }
    
    /**
     * Indicate that the consumable is for other purposes.
     */
    public function other(): static
    {
        return $this->state(fn (array $attributes) => [
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(
                ['code' => 'other'],
                [
                    'name' => 'Other',
                    'description' => 'Other consumables',
                    'color' => 'gray',
                    'is_active' => true,
                    'sort_order' => 5,
                ]
            )->id,
            'name' => fake()->randomElement(['Sanitizer', 'Hydrogen Peroxide', 'pH Test Strips', 'Scissors', 'Gloves', 'Pens']),
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
                'total_quantity' => fake()->numberBetween(0, $restockThreshold - 1),
            ];
        });
    }
} 
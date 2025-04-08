<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\SeedVariety;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['seed', 'soil', 'consumable'];
        $itemType = fake()->randomElement($types);
        
        $name = match($itemType) {
            'seed' => SeedVariety::factory()->create()->name . ' Seeds',
            'soil' => fake()->randomElement(['Organic Potting Mix', 'Coco Coir', 'Seed Starting Mix', 'Perlite', 'Vermiculite']),
            'consumable' => fake()->randomElement(['10x20 Tray', '10x10 Tray', 'Humidity Dome', 'Seedling Tray', 'Microgreens Tray', 'Hydrogen Peroxide', 'Sanitizer', 'pH Down', 'pH Up', 'Nutrient Solution']),
        };
        
        $unit = match($itemType) {
            'seed' => fake()->randomElement(['lb', 'kg', 'oz', 'g']),
            'soil' => fake()->randomElement(['bag', 'lb', 'kg', 'cu ft']),
            'consumable' => fake()->randomElement(['unit', 'bottle', 'gallon', 'liter', 'oz']),
        };
        
        return [
            'name' => $name,
            'item_type' => $itemType,
            'supplier_id' => Supplier::factory()->create(['type' => $itemType])->id,
            'quantity' => fake()->randomFloat(2, 1, 100),
            'unit' => $unit,
            'restock_threshold' => fake()->randomFloat(2, 1, 10),
            'restock_quantity' => fake()->randomFloat(2, 5, 20),
            'notes' => fake()->optional(0.6)->paragraph(),
        ];
    }
    
    /**
     * Indicate that the inventory item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
    
    /**
     * Indicate that the inventory item is a seed.
     */
    public function seed(): static
    {
        $seedVariety = SeedVariety::factory()->create();
        
        return $this->state(fn (array $attributes) => [
            'name' => $seedVariety->name . ' Seeds',
            'item_type' => 'seed',
            'supplier_id' => $seedVariety->supplier_id,
            'unit' => fake()->randomElement(['lb', 'kg', 'oz', 'g']),
        ]);
    }
    
    /**
     * Indicate that the inventory item is soil.
     */
    public function soil(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['Organic Potting Mix', 'Coco Coir', 'Seed Starting Mix', 'Perlite', 'Vermiculite']),
            'item_type' => 'soil',
            'supplier_id' => Supplier::factory()->soil()->create()->id,
            'unit' => fake()->randomElement(['bag', 'lb', 'kg', 'cu ft']),
        ]);
    }
    
    /**
     * Indicate that the inventory item is a consumable.
     */
    public function consumable(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['10x20 Tray', '10x10 Tray', 'Humidity Dome', 'Seedling Tray', 'Microgreens Tray', 'Hydrogen Peroxide', 'Sanitizer', 'pH Down', 'pH Up', 'Nutrient Solution']),
            'item_type' => 'consumable',
            'supplier_id' => Supplier::factory()->consumable()->create()->id,
            'unit' => fake()->randomElement(['unit', 'bottle', 'gallon', 'liter', 'oz']),
        ]);
    }
    
    /**
     * Indicate that the inventory item is for a specific supplier.
     */
    public function forSupplier(Supplier $supplier): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_id' => $supplier->id,
            'item_type' => $supplier->type,
        ]);
    }
    
    /**
     * Indicate that the inventory item is low in stock.
     */
    public function lowStock(): static
    {
        return $this->state(function (array $attributes) {
            $restockThreshold = $attributes['restock_threshold'] ?? fake()->randomFloat(2, 5, 10);
            
            return [
                'quantity' => fake()->randomFloat(2, 0, $restockThreshold - 0.1),
            ];
        });
    }
} 
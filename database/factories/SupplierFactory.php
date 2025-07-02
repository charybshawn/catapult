<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\SupplierType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $typeCodes = ['soil', 'seed', 'consumable'];
        $randomTypeCode = fake()->randomElement($typeCodes);
        
        return [
            'name' => fake()->company(),
            'supplier_type_id' => SupplierType::findByCode($randomTypeCode)?->id ?? SupplierType::findByCode('other')?->id,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'notes' => fake()->optional(0.7)->paragraph(),
            'is_active' => true,
        ];
    }
    
    /**
     * Indicate that the supplier is for soil.
     */
    public function soil(): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_type_id' => SupplierType::findByCode('soil')?->id,
        ]);
    }
    
    /**
     * Indicate that the supplier is for seeds.
     */
    public function seed(): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_type_id' => SupplierType::findByCode('seed')?->id,
        ]);
    }
    
    /**
     * Indicate that the supplier is for consumables.
     */
    public function consumable(): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_type_id' => SupplierType::findByCode('consumable')?->id,
        ]);
    }
    
    /**
     * Indicate that the supplier is for packaging.
     */
    public function packaging(): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_type_id' => SupplierType::findByCode('packaging')?->id,
        ]);
    }
    
    /**
     * Indicate that the supplier is for other.
     */
    public function other(): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_type_id' => SupplierType::findByCode('other')?->id,
        ]);
    }
    
    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
} 
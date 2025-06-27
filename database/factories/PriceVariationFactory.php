<?php

namespace Database\Factories;

use App\Models\PriceVariation;
use App\Models\Product;
use App\Models\PackagingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceVariation>
 */
class PriceVariationFactory extends Factory
{
    protected $model = PriceVariation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $packagingTypes = ['4oz Container', '8oz Container', '1lb Container', 'Bulk/lb'];
        $selectedType = $this->faker->randomElement($packagingTypes);
        
        return [
            'product_id' => Product::factory(),
            'name' => $selectedType,
            'sku' => strtoupper($this->faker->lexify('??###')),
            'fill_weight' => $this->getWeightForType($selectedType),
            'price' => $this->faker->randomFloat(2, 2, 25),
            'is_default' => false,
            'is_global' => $this->faker->boolean(30),
            'is_active' => true,
        ];
    }

    /**
     * Create a default price variation.
     */
    public function default(): static
    {
        return $this->state([
            'is_default' => true,
            'name' => '4oz Container',
            'fill_weight' => 113.4,
        ]);
    }

    /**
     * Create a bulk price variation.
     */
    public function bulk(): static
    {
        return $this->state([
            'name' => 'Bulk/lb',
            'fill_weight' => 453.6,
            'price' => $this->faker->randomFloat(2, 8, 20),
        ]);
    }

    /**
     * Create a container-based price variation.
     */
    public function container(string $size = '4oz'): static
    {
        return $this->state([
            'name' => "{$size} Container",
            'fill_weight' => $this->getWeightForSize($size),
        ]);
    }

    /**
     * Create an inactive price variation.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * Create a global price variation.
     */
    public function global(): static
    {
        return $this->state([
            'is_global' => true,
        ]);
    }

    /**
     * Create variation for specific product.
     */
    public function forProduct(Product $product): static
    {
        return $this->state([
            'product_id' => $product->id,
        ]);
    }

    /**
     * Create variation with specific price.
     */
    public function withPrice(float $price): static
    {
        return $this->state([
            'price' => $price,
        ]);
    }

    /**
     * Get weight in grams for packaging type.
     */
    private function getWeightForType(string $type): float
    {
        return match($type) {
            '4oz Container' => 113.4,
            '8oz Container' => 226.8,
            '1lb Container' => 453.6,
            'Bulk/lb' => 453.6,
            default => 113.4,
        };
    }

    /**
     * Get weight in grams for container size.
     */
    private function getWeightForSize(string $size): float
    {
        return match($size) {
            '2oz' => 56.7,
            '4oz' => 113.4,
            '8oz' => 226.8,
            '12oz' => 340.2,
            '1lb' => 453.6,
            '2lb' => 907.2,
            default => 113.4,
        };
    }

}
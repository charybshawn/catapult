<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Models\PriceVariation;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductPricingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_caps_wholesale_discount_at_100_percent()
    {
        // Create a user with an unrealistic discount percentage
        $user = User::factory()->create([
            'customer_type' => 'wholesale',
            'wholesale_discount_percentage' => 150.0, // 150% discount (unrealistic)
        ]);

        // Create a product with a small price
        $product = Product::factory()->create([
            'base_price' => 10.00,
        ]);

        // Create a price variation
        $priceVariation = PriceVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 10.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        // Test that the discount is capped at 100%
        $discountPercentage = $user->getWholesaleDiscountPercentage($product);
        $this->assertEquals(100, $discountPercentage, 'Discount should be capped at 100%');

        // Test that the wholesale price is never negative
        $wholesalePrice = $product->getWholesalePrice($priceVariation->id, null, $user);
        $this->assertGreaterThanOrEqual(0, $wholesalePrice, 'Wholesale price should never be negative');
        $this->assertEquals(0, $wholesalePrice, 'With 100% discount, price should be 0');
    }

    /** @test */
    public function it_handles_extremely_high_product_discount_percentages()
    {
        // Create a wholesale user
        $user = User::factory()->create([
            'customer_type' => 'wholesale',
            'wholesale_discount_percentage' => null, // No user-specific discount
        ]);

        // Create a product with an unrealistic discount percentage
        $product = Product::factory()->create([
            'base_price' => 10.00,
            'wholesale_discount_percentage' => 500.0, // 500% discount (unrealistic)
        ]);

        // Create a price variation
        $priceVariation = PriceVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 10.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        // Test that the discount is capped at 100%
        $discountPercentage = $user->getWholesaleDiscountPercentage($product);
        $this->assertEquals(100, $discountPercentage, 'Product discount should be capped at 100%');

        // Test that the wholesale price is never negative
        $wholesalePrice = $product->getWholesalePrice($priceVariation->id, null, $user);
        $this->assertGreaterThanOrEqual(0, $wholesalePrice, 'Wholesale price should never be negative');
        $this->assertEquals(0, $wholesalePrice, 'With 100% discount, price should be 0');
    }

    /** @test */
    public function it_calculates_correct_wholesale_price_for_normal_discounts()
    {
        // Create a wholesale user with a normal discount
        $user = User::factory()->create([
            'customer_type' => 'wholesale',
            'wholesale_discount_percentage' => 25.0, // 25% discount
        ]);

        // Create a product
        $product = Product::factory()->create([
            'base_price' => 10.00,
        ]);

        // Create a price variation
        $priceVariation = PriceVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 10.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        // Test normal discount calculation
        $wholesalePrice = $product->getPriceForSpecificCustomer($user, $priceVariation->id);
        $expectedPrice = 10.00 - (10.00 * 0.25); // $10.00 - $2.50 = $7.50
        $this->assertEquals(7.50, $wholesalePrice, 'Wholesale price should be correctly calculated');
    }

    /** @test */
    public function it_handles_very_small_prices_correctly()
    {
        // Create a wholesale user
        $user = User::factory()->create([
            'customer_type' => 'wholesale',
            'wholesale_discount_percentage' => 25.0, // 25% discount
        ]);

        // Create a product
        $product = Product::factory()->create([
            'base_price' => 10.00,
        ]);

        // Create a price variation with a very small price (like in the failing test)
        $priceVariation = PriceVariation::factory()->create([
            'product_id' => $product->id,
            'price' => 0.01, // Very small price
            'is_default' => true,
            'is_active' => true,
        ]);

        // Test that small prices are handled correctly
        $wholesalePrice = $product->getPriceForSpecificCustomer($user, $priceVariation->id);
        $expectedPrice = 0.01 - (0.01 * 0.25); // $0.01 - $0.0025 = $0.0075
        $this->assertEquals(0.0075, $wholesalePrice, 'Small wholesale prices should be calculated correctly');
        $this->assertLessThan(1, $wholesalePrice, 'Result should not be unrealistically large');
    }
}
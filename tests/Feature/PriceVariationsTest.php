<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PriceVariation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceVariationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that price variations can be created for a product with specific prices.
     */
    public function test_price_variations_can_be_created_with_specific_prices(): void
    {
        // Create a product without price variations (disable auto-creation)
        $product = new Product();
        $product->name = 'Test Product';
        $product->description = 'A test product with price variations';
        $product->active = true;
        // Don't set prices here to avoid auto-creation in boot
        $product->saveQuietly();
        
        // Verify no price variations exist yet
        $this->assertEquals(0, $product->priceVariations()->count());
        
        // Explicitly create the variations with specific prices
        $priceData = [
            'base_price' => 100.00,
            'wholesale_price' => 80.00,
            'bulk_price' => 70.00,
            'special_price' => 90.00,
        ];
        $variations = $product->createAllStandardPriceVariations($priceData);

        // Reload the product to get fresh relationship data
        $product = Product::find($product->id);
        
        // Should have 4 price variations created
        $this->assertEquals(4, $product->priceVariations()->count());
        
        // Verify each type exists with correct values
        $defaultVariation = $product->priceVariations()->where('name', 'Default')->first();
        $this->assertNotNull($defaultVariation);
        $this->assertEquals(100.00, $defaultVariation->price);
        $this->assertTrue((bool)$defaultVariation->is_default);
        
        $wholesaleVariation = $product->priceVariations()->where('name', 'Wholesale')->first();
        $this->assertNotNull($wholesaleVariation);
        $this->assertEquals(80.00, $wholesaleVariation->price);
        
        $bulkVariation = $product->priceVariations()->where('name', 'Bulk')->first();
        $this->assertNotNull($bulkVariation);
        $this->assertEquals(70.00, $bulkVariation->price);
        
        $specialVariation = $product->priceVariations()->where('name', 'Special')->first();
        $this->assertNotNull($specialVariation);
        $this->assertEquals(90.00, $specialVariation->price);
    }
    
    /**
     * Test that price variations are updated when product prices change.
     */
    public function test_price_variations_can_be_updated(): void
    {
        // Create a product without price variations
        $product = new Product();
        $product->name = 'Test Product';
        $product->description = 'A test product with price variations';
        $product->active = true;
        $product->saveQuietly();
        
        // Manually create a default price variation
        $defaultVariation = $product->createDefaultPriceVariation(['price' => 100.00]);
        
        // Verify we have just one price variation
        $this->assertEquals(1, $product->priceVariations()->count());
        
        // Update the default variation
        $defaultVariation->update(['price' => 110.00]);
        
        // Create additional price variations
        $wholesaleVariation = $product->createWholesalePriceVariation(85.00);
        $bulkVariation = $product->createBulkPriceVariation(75.00);
        $specialVariation = $product->createSpecialPriceVariation(95.00);
        
        // Refresh the product from the database
        $product = Product::find($product->id);
        
        // Should now have 4 price variations
        $this->assertEquals(4, $product->priceVariations()->count());
        
        // Verify each type exists with correct values
        $defaultVariation = $product->priceVariations()->where('name', 'Default')->first();
        $this->assertNotNull($defaultVariation);
        $this->assertEquals(110.00, $defaultVariation->price);
        
        $wholesaleVariation = $product->priceVariations()->where('name', 'Wholesale')->first();
        $this->assertNotNull($wholesaleVariation);
        $this->assertEquals(85.00, $wholesaleVariation->price);
        
        $bulkVariation = $product->priceVariations()->where('name', 'Bulk')->first();
        $this->assertNotNull($bulkVariation);
        $this->assertEquals(75.00, $bulkVariation->price);
        
        $specialVariation = $product->priceVariations()->where('name', 'Special')->first();
        $this->assertNotNull($specialVariation);
        $this->assertEquals(95.00, $specialVariation->price);
    }
    
    /**
     * Test that price variations can be created manually with custom attributes.
     */
    public function test_price_variations_can_be_created_manually(): void
    {
        // Create a product
        $product = new Product();
        $product->name = 'Test Product';
        $product->description = 'A test product';
        $product->active = true;
        $product->saveQuietly();
        
        // Create a default price variation
        $defaultVariation = $product->createDefaultPriceVariation(['price' => 100.00]);
        
        // Create a custom price variation
        $customVariation = $product->createCustomPriceVariation('Premium', 120.00, 'item', [
            'is_active' => true,
        ]);
        
        // Refresh the product from the database
        $product = Product::find($product->id);
        
        // Should have 2 price variations (default + custom)
        $this->assertEquals(2, $product->priceVariations()->count());
        
        // Verify the custom variation exists with correct values
        $customVariation = $product->priceVariations()->where('name', 'Premium')->first();
        $this->assertNotNull($customVariation);
        $this->assertEquals(120.00, $customVariation->price);
        $this->assertEquals('item', $customVariation->unit);
        $this->assertEquals('Premium', $customVariation->name);
    }
} 
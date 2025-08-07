<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Filament\Resources\OrderSimulatorResource\Services\OrderCalculationService;
use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductMix;
use App\Models\MasterSeedCatalog;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderCalculationService();
    }

    /** @test */
    public function it_calculates_variety_requirements_for_single_variety_products()
    {
        // Create category for product
        $category = Category::factory()->create(['name' => 'Test Category']);

        // Create a master seed catalog entry (single variety)
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Basil',
            'cultivar_name' => 'Genovese',
            'category' => 'herbs',
            'is_active' => true,
        ]);

        // Create a single variety product
        $product = Product::create([
            'name' => 'Basil Genovese Seeds',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create price variation
        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Default',
            'price' => 10.00,
            'fill_weight' => 113.4, // 4oz container in grams
            'is_default' => true,
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 3,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertArrayHasKey('variety_totals', $results);
        $this->assertArrayHasKey('item_breakdown', $results);
        $this->assertArrayHasKey('summary', $results);

        // Check variety totals
        $this->assertCount(1, $results['variety_totals']);
        $varietyTotal = $results['variety_totals'][0];
        
        $this->assertEquals($variety->id, $varietyTotal['variety_id']);
        $this->assertEquals('Basil - Genovese', $varietyTotal['variety_name']);
        $this->assertEquals(340.2, $varietyTotal['total_grams']); // 3 * 113.4g

        // Check item breakdown
        $this->assertCount(1, $results['item_breakdown']);
        $item = $results['item_breakdown'][0];
        
        $this->assertEquals('Basil Genovese Seeds', $item['product_name']);
        $this->assertEquals('Default', $item['package_size']);
        $this->assertEquals(3, $item['quantity']);
        $this->assertEquals(113.4, $item['fill_weight']);
        $this->assertEquals(340.2, $item['total_grams']);
        $this->assertEquals('single', $item['type']);
        
        // Check varieties in breakdown
        $this->assertCount(1, $item['varieties']);
        $variety_breakdown = $item['varieties'][0];
        $this->assertEquals('Basil - Genovese', $variety_breakdown['name']);
        $this->assertEquals(340.2, $variety_breakdown['grams']);
        $this->assertEquals(100, $variety_breakdown['percentage']);

        // Check summary
        $this->assertEquals(1, $results['summary']['total_varieties']);
        $this->assertEquals(1, $results['summary']['total_items']);
        $this->assertEquals(340.2, $results['summary']['total_grams']);
    }

    /** @test */
    public function it_calculates_variety_requirements_for_product_mix()
    {
        // Create category for product
        $category = Category::factory()->create(['name' => 'Mix Category']);

        // Create master seed catalog entries for mix
        $variety1 = MasterSeedCatalog::create([
            'common_name' => 'Lettuce',
            'cultivar_name' => 'Buttercrunch',
            'category' => 'greens',
            'is_active' => true,
        ]);

        $variety2 = MasterSeedCatalog::create([
            'common_name' => 'Lettuce',
            'cultivar_name' => 'Red Oak Leaf',
            'category' => 'greens',
            'is_active' => true,
        ]);

        // Create product mix
        $productMix = ProductMix::create([
            'name' => 'Lettuce Mix',
            'description' => 'Mixed lettuce varieties',
            'is_active' => true,
        ]);

        // Attach varieties to mix with percentages
        $productMix->masterSeedCatalogs()->attach($variety1->id, ['percentage' => 60]);
        $productMix->masterSeedCatalogs()->attach($variety2->id, ['percentage' => 40]);

        // Create mix product
        $product = Product::create([
            'name' => 'Lettuce Salad Mix',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        // Create price variation
        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Default Mix',
            'price' => 15.00,
            'fill_weight' => 226.8, // 8oz container in grams
            'is_default' => true,
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 2,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Check variety totals - should have 2 varieties
        $this->assertCount(2, $results['variety_totals']);
        
        // Find each variety total
        $variety1Total = collect($results['variety_totals'])
            ->where('variety_id', $variety1->id)
            ->first();
        $variety2Total = collect($results['variety_totals'])
            ->where('variety_id', $variety2->id)
            ->first();

        $this->assertNotNull($variety1Total);
        $this->assertNotNull($variety2Total);

        // Verify calculations: 2 containers * 226.8g each = 453.6g total
        // Variety1: 60% of 453.6g = 272.16g
        // Variety2: 40% of 453.6g = 181.44g
        $this->assertEquals(272.16, $variety1Total['total_grams']);
        $this->assertEquals(181.44, $variety2Total['total_grams']);

        // Check item breakdown
        $this->assertCount(1, $results['item_breakdown']);
        $item = $results['item_breakdown'][0];
        
        $this->assertEquals('Lettuce Salad Mix', $item['product_name']);
        $this->assertEquals('Default Mix', $item['package_size']);
        $this->assertEquals(2, $item['quantity']);
        $this->assertEquals(226.8, $item['fill_weight']);
        $this->assertEquals(453.6, $item['total_grams']);
        $this->assertEquals('mix', $item['type']);

        // Check varieties in breakdown
        $this->assertCount(2, $item['varieties']);
        
        // Check summary
        $this->assertEquals(2, $results['summary']['total_varieties']);
        $this->assertEquals(1, $results['summary']['total_items']);
        $this->assertEquals(453.6, $results['summary']['total_grams']);
    }

    /** @test */
    public function it_handles_mixed_single_and_mix_products()
    {
        // Create category
        $category = Category::factory()->create(['name' => 'Mixed Category']);

        // Create varieties
        $singleVariety = MasterSeedCatalog::create([
            'common_name' => 'Tomato',
            'cultivar_name' => 'Cherry',
            'category' => 'tomatoes',
            'is_active' => true,
        ]);

        $mixVariety1 = MasterSeedCatalog::create([
            'common_name' => 'Pepper',
            'cultivar_name' => 'Bell',
            'category' => 'peppers',
            'is_active' => true,
        ]);

        $mixVariety2 = MasterSeedCatalog::create([
            'common_name' => 'Pepper',
            'cultivar_name' => 'Hot',
            'category' => 'peppers',
            'is_active' => true,
        ]);

        // Create single variety product
        $singleProduct = Product::create([
            'name' => 'Cherry Tomato Seeds',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $singleVariety->id,
        ]);

        // Create product mix
        $productMix = ProductMix::create([
            'name' => 'Pepper Mix',
            'description' => 'Bell and hot peppers',
            'is_active' => true,
        ]);

        $productMix->masterSeedCatalogs()->attach($mixVariety1->id, ['percentage' => 70]);
        $productMix->masterSeedCatalogs()->attach($mixVariety2->id, ['percentage' => 30]);

        // Create mix product
        $mixProduct = Product::create([
            'name' => 'Pepper Mix Seeds',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        // Create price variations
        $singlePriceVariation = PriceVariation::create([
            'product_id' => $singleProduct->id,
            'name' => 'Single Pack',
            'price' => 5.00,
            'fill_weight' => 113.4, // 4oz
            'is_default' => true,
            'is_active' => true,
        ]);

        $mixPriceVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Mix Pack',
            'price' => 8.00,
            'fill_weight' => 113.4, // 4oz
            'is_default' => true,
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $singleProduct->id,
                'price_variation_id' => $singlePriceVariation->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $mixPriceVariation->id,
                'quantity' => 2,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should have 3 varieties total (1 single + 2 from mix)
        $this->assertCount(3, $results['variety_totals']);

        // Check that both types of products are in breakdown
        $this->assertCount(2, $results['item_breakdown']);

        // Verify single product item
        $singleItem = collect($results['item_breakdown'])
            ->where('product_name', 'Cherry Tomato Seeds')
            ->first();
        
        $this->assertEquals('single', $singleItem['type']);
        $this->assertCount(1, $singleItem['varieties']);

        // Verify mix product item
        $mixItem = collect($results['item_breakdown'])
            ->where('product_name', 'Pepper Mix Seeds')
            ->first();
        
        $this->assertEquals('mix', $mixItem['type']);
        $this->assertCount(2, $mixItem['varieties']);

        // Check summary totals
        $this->assertEquals(3, $results['summary']['total_varieties']);
        $this->assertEquals(2, $results['summary']['total_items']);
    }

    /** @test */
    public function it_handles_empty_order_items()
    {
        $results = $this->service->calculateVarietyRequirements([]);

        $this->assertArrayHasKey('variety_totals', $results);
        $this->assertArrayHasKey('item_breakdown', $results);
        $this->assertArrayHasKey('summary', $results);

        $this->assertEmpty($results['variety_totals']);
        $this->assertEmpty($results['item_breakdown']);
        $this->assertEquals(0, $results['summary']['total_varieties']);
        $this->assertEquals(0, $results['summary']['total_items']);
        $this->assertEquals(0, $results['summary']['total_grams']);
    }

    /** @test */
    public function it_skips_invalid_order_items()
    {
        $orderItems = [
            // Missing product_id
            [
                'price_variation_id' => 999,
                'quantity' => 1,
            ],
            // Missing price_variation_id
            [
                'product_id' => 999,
                'quantity' => 1,
            ],
            // Missing quantity
            [
                'product_id' => 999,
                'price_variation_id' => 999,
            ],
            // Zero quantity
            [
                'product_id' => 999,
                'price_variation_id' => 999,
                'quantity' => 0,
            ],
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertEmpty($results['variety_totals']);
        $this->assertEmpty($results['item_breakdown']);
        $this->assertEquals(0, $results['summary']['total_varieties']);
    }

    /** @test */
    public function it_skips_non_existent_products_and_variations()
    {
        $orderItems = [
            [
                'product_id' => 999999, // Non-existent product
                'price_variation_id' => 999999, // Non-existent price variation
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertEmpty($results['variety_totals']);
        $this->assertEmpty($results['item_breakdown']);
        $this->assertEquals(0, $results['summary']['total_varieties']);
    }

    /** @test */
    public function it_handles_products_without_varieties_or_mixes()
    {
        // Create category
        $category = Category::factory()->create(['name' => 'No Variety Category']);

        // Create product without master_seed_catalog_id or product_mix_id
        $product = Product::create([
            'name' => 'Generic Product',
            'active' => true,
            'category_id' => $category->id,
            // No master_seed_catalog_id or product_mix_id
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Default',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'is_default' => true,
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should not create variety totals for products without varieties
        $this->assertEmpty($results['variety_totals']);
        $this->assertEmpty($results['item_breakdown']);
        $this->assertEquals(0, $results['summary']['total_varieties']);
    }

    /** @test */
    public function it_properly_rounds_decimal_weights()
    {
        // Create category and variety
        $category = Category::factory()->create(['name' => 'Rounding Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Test',
            'cultivar_name' => 'Rounding',
            'category' => 'test',
            'is_active' => true,
        ]);

        // Create product
        $product = Product::create([
            'name' => 'Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create price variation with unusual fill weight
        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Unusual Weight',
            'price' => 10.00,
            'fill_weight' => 33.333, // This will create fractional results
            'is_default' => true,
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 3,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Check that totals are properly rounded to 2 decimal places
        $varietyTotal = $results['variety_totals'][0];
        $expectedTotal = round(3 * 33.333, 2); // 99.999 rounds to 100.00
        
        $this->assertEquals($expectedTotal, $varietyTotal['total_grams']);
        
        // Verify the same rounding applies to summary
        $this->assertEquals($expectedTotal, $results['summary']['total_grams']);
    }

    /** @test */
    public function it_aggregates_same_variety_from_multiple_products()
    {
        // Create shared variety
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Shared',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $category = Category::factory()->create(['name' => 'Aggregation Category']);

        // Create two different products with same variety
        $product1 = Product::create([
            'name' => 'Product One',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $product2 = Product::create([
            'name' => 'Product Two',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation1 = PriceVariation::create([
            'product_id' => $product1->id,
            'name' => 'Small',
            'price' => 5.00,
            'fill_weight' => 113.4,
            'is_default' => true,
            'is_active' => true,
        ]);

        $priceVariation2 = PriceVariation::create([
            'product_id' => $product2->id,
            'name' => 'Large',
            'price' => 8.00,
            'fill_weight' => 226.8,
            'is_default' => true,
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product1->id,
                'price_variation_id' => $priceVariation1->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $product2->id,
                'price_variation_id' => $priceVariation2->id,
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should have only 1 variety total (aggregated)
        $this->assertCount(1, $results['variety_totals']);

        $varietyTotal = $results['variety_totals'][0];
        $expectedTotal = (2 * 113.4) + (1 * 226.8); // 226.8 + 226.8 = 453.6
        
        $this->assertEquals($variety->id, $varietyTotal['variety_id']);
        $this->assertEquals($expectedTotal, $varietyTotal['total_grams']);

        // Should have 2 products listed under this variety
        $this->assertCount(2, $varietyTotal['products']);

        // But item breakdown should still show 2 separate items
        $this->assertCount(2, $results['item_breakdown']);
    }
}
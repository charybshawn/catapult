<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Filament\Resources\OrderSimulatorResource\Services\OrderCalculationService;
use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductMix;
use App\Models\MasterSeedCatalog;
use App\Models\Category;
use App\Models\PackagingType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderSimulatorPricingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected OrderCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderCalculationService();
    }

    /** @test */
    public function it_integrates_with_product_pricing_system()
    {
        // Create packaging types for different sizes
        $container4oz = PackagingType::create([
            'name' => '4oz Container',
            'description' => '4 ounce container',
            'is_active' => true,
        ]);

        $container8oz = PackagingType::create([
            'name' => '8oz Container', 
            'description' => '8 ounce container',
            'is_active' => true,
        ]);

        // Create category and variety
        $category = Category::factory()->create(['name' => 'Pricing Integration']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Integration',
            'cultivar_name' => 'Test',
            'category' => 'test',
            'is_active' => true,
        ]);

        // Create product
        $product = Product::create([
            'name' => 'Integration Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
            'base_price' => 10.00,
            'wholesale_price' => 8.00,
            'bulk_price' => 6.00,
        ]);

        // Create price variations with different packaging and pricing tiers
        $retailVariation = PriceVariation::create([
            'product_id' => $product->id,
            'packaging_type_id' => $container4oz->id,
            'name' => 'Retail - 4oz Container',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'pricing_unit' => 'per_item',
            'is_active' => true,
        ]);

        $wholesaleVariation = PriceVariation::create([
            'product_id' => $product->id,
            'packaging_type_id' => $container8oz->id,
            'name' => 'Wholesale - 8oz Container',
            'price' => 8.00,
            'fill_weight' => 226.8,
            'pricing_type' => 'wholesale',
            'pricing_unit' => 'per_item',
            'is_active' => true,
        ]);

        $bulkVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - per lb',
            'price' => 6.00,
            'fill_weight' => 453.6,
            'pricing_type' => 'bulk',
            'pricing_unit' => 'per_lb',
            'is_active' => true,
        ]);

        // Test order calculation with different pricing tiers
        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $retailVariation->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $wholesaleVariation->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $bulkVariation->id,
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should have 1 variety total (all same variety)
        $this->assertCount(1, $results['variety_totals']);

        $varietyTotal = $results['variety_totals'][0];
        
        // Total weight should be: (2 * 113.4) + (1 * 226.8) + (1 * 453.6) = 1,007.2g
        $expectedTotal = (2 * 113.4) + (1 * 226.8) + (1 * 453.6);
        $this->assertEquals($expectedTotal, $varietyTotal['total_grams']);

        // Should have 3 different product entries under this variety
        $this->assertCount(3, $varietyTotal['products']);

        // Check that different pricing types are handled correctly in breakdown
        $this->assertCount(3, $results['item_breakdown']);

        // Verify each breakdown item has correct pricing information
        $retailItem = collect($results['item_breakdown'])
            ->where('package_size', 'Retail - 4oz Container')
            ->first();
        
        $this->assertEquals(2, $retailItem['quantity']);
        $this->assertEquals(113.4, $retailItem['fill_weight']);
        $this->assertEquals(226.8, $retailItem['total_grams']);

        $wholesaleItem = collect($results['item_breakdown'])
            ->where('package_size', 'Wholesale - 8oz Container')
            ->first();
        
        $this->assertEquals(1, $wholesaleItem['quantity']);
        $this->assertEquals(226.8, $wholesaleItem['fill_weight']);

        $bulkItem = collect($results['item_breakdown'])
            ->where('package_size', 'Bulk - per lb')
            ->first();
        
        $this->assertEquals(1, $bulkItem['quantity']);
        $this->assertEquals(453.6, $bulkItem['fill_weight']);
    }

    /** @test */
    public function it_handles_unit_measurement_conversions_correctly()
    {
        $category = Category::factory()->create(['name' => 'Unit Conversion']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Unit',
            'cultivar_name' => 'Test',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Unit Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create variations with different unit measurements
        $gramsVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Per Gram',
            'price' => 0.10,
            'fill_weight' => 100.0, // 100 grams
            'pricing_unit' => 'per_g',
            'is_active' => true,
        ]);

        $kilogramsVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Per Kilogram',
            'price' => 100.00,
            'fill_weight' => 1000.0, // 1000 grams (1 kg)
            'pricing_unit' => 'per_kg',
            'is_active' => true,
        ]);

        $poundsVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Per Pound',
            'price' => 45.36,
            'fill_weight' => 453.592, // 1 pound in grams
            'pricing_unit' => 'per_lb',
            'is_active' => true,
        ]);

        $ouncesVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Per Ounce',
            'price' => 2.83,
            'fill_weight' => 28.3495, // 1 ounce in grams
            'pricing_unit' => 'per_oz',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $gramsVariation->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $kilogramsVariation->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $poundsVariation->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $ouncesVariation->id,
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Test unit conversion methods on PriceVariation model
        $this->assertEquals(1.0, $gramsVariation->getUnitToGramsConversionFactor());
        $this->assertEquals(1000.0, $kilogramsVariation->getUnitToGramsConversionFactor());
        $this->assertEquals(453.592, $poundsVariation->getUnitToGramsConversionFactor());
        $this->assertEquals(28.3495, $ouncesVariation->getUnitToGramsConversionFactor());

        // Test conversion methods
        $this->assertEquals(100.0, $gramsVariation->convertToGrams(100));
        $this->assertEquals(1000.0, $kilogramsVariation->convertToGrams(1));
        $this->assertEquals(453.592, $poundsVariation->convertToGrams(1));
        $this->assertEquals(28.3495, $ouncesVariation->convertToGrams(1));

        // Verify total calculations are correct
        $expectedTotal = 100.0 + 1000.0 + 453.592 + 28.3495;
        $this->assertEquals(round($expectedTotal, 2), $results['summary']['total_grams']);

        // Verify display units
        $this->assertEquals('grams', $gramsVariation->getDisplayUnit());
        $this->assertEquals('kg', $kilogramsVariation->getDisplayUnit());
        $this->assertEquals('lbs', $poundsVariation->getDisplayUnit());
        $this->assertEquals('oz', $ouncesVariation->getDisplayUnit());
    }

    /** @test */
    public function it_prevents_unit_conversion_errors()
    {
        $category = Category::factory()->create(['name' => 'Error Prevention']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Error',
            'cultivar_name' => 'Prevention',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Error Prevention Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create variation with invalid/unknown unit
        $unknownUnitVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Unknown Unit',
            'price' => 10.00,
            'fill_weight' => 100.0,
            'pricing_unit' => 'invalid_unit',
            'is_active' => true,
        ]);

        // Create variation with null unit
        $nullUnitVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Null Unit',
            'price' => 10.00,
            'fill_weight' => 100.0,
            'pricing_unit' => null,
            'is_active' => true,
        ]);

        // Test that invalid units default to 1.0 conversion factor
        $this->assertEquals(1.0, $unknownUnitVariation->getUnitToGramsConversionFactor());
        $this->assertEquals(1.0, $nullUnitVariation->getUnitToGramsConversionFactor());

        // Test conversion with invalid units
        $this->assertEquals(100.0, $unknownUnitVariation->convertToGrams(100));
        $this->assertEquals(100.0, $nullUnitVariation->convertToGrams(100));

        // Test display units with invalid/null units
        $this->assertEquals('units', $unknownUnitVariation->getDisplayUnit());
        $this->assertEquals('units', $nullUnitVariation->getDisplayUnit());

        // Test that calculations still work correctly
        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $unknownUnitVariation->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $nullUnitVariation->id,
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertCount(1, $results['variety_totals']);
        $this->assertEquals(200.0, $results['summary']['total_grams']); // 2 * 100g
    }

    /** @test */
    public function it_handles_complex_product_mix_with_different_measurements()
    {
        $category = Category::factory()->create(['name' => 'Complex Mix']);

        // Create varieties for the mix
        $variety1 = MasterSeedCatalog::create([
            'common_name' => 'Mix Component 1',
            'cultivar_name' => 'First',
            'category' => 'test',
            'is_active' => true,
        ]);

        $variety2 = MasterSeedCatalog::create([
            'common_name' => 'Mix Component 2',
            'cultivar_name' => 'Second',
            'category' => 'test',
            'is_active' => true,
        ]);

        $variety3 = MasterSeedCatalog::create([
            'common_name' => 'Mix Component 3',
            'cultivar_name' => 'Third',
            'category' => 'test',
            'is_active' => true,
        ]);

        // Create product mix with three components
        $productMix = ProductMix::create([
            'name' => 'Complex Three-Way Mix',
            'description' => 'Three component mix for testing',
            'is_active' => true,
        ]);

        // Attach varieties with specific percentages that add to 100%
        $productMix->masterSeedCatalogs()->attach($variety1->id, ['percentage' => 50]);
        $productMix->masterSeedCatalogs()->attach($variety2->id, ['percentage' => 30]);
        $productMix->masterSeedCatalogs()->attach($variety3->id, ['percentage' => 20]);

        // Verify the mix validates correctly
        $this->assertTrue($productMix->validatePercentages());

        // Create mix product
        $mixProduct = Product::create([
            'name' => 'Complex Mix Product',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        // Create price variations with different measurements
        $smallMixVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Small Mix - 4oz',
            'price' => 12.00,
            'fill_weight' => 113.4, // 4oz in grams
            'pricing_unit' => 'per_item',
            'is_active' => true,
        ]);

        $largeMixVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Large Mix - 1lb',
            'price' => 40.00,
            'fill_weight' => 453.6, // 1 pound in grams
            'pricing_unit' => 'per_lb',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $smallMixVariation->id,
                'quantity' => 4,
            ],
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $largeMixVariation->id,
                'quantity' => 2,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should have 3 variety totals (one for each component)
        $this->assertCount(3, $results['variety_totals']);

        // Calculate expected totals:
        // Small containers: 4 * 113.4g = 453.6g
        // Large containers: 2 * 453.6g = 907.2g
        // Total mix weight: 453.6 + 907.2 = 1,360.8g
        
        // Component weights:
        // Variety1 (50%): 1,360.8 * 0.5 = 680.4g
        // Variety2 (30%): 1,360.8 * 0.3 = 408.24g
        // Variety3 (20%): 1,360.8 * 0.2 = 272.16g

        $variety1Total = collect($results['variety_totals'])
            ->where('variety_id', $variety1->id)
            ->first();
        $variety2Total = collect($results['variety_totals'])
            ->where('variety_id', $variety2->id)
            ->first();
        $variety3Total = collect($results['variety_totals'])
            ->where('variety_id', $variety3->id)
            ->first();

        $this->assertEquals(680.4, $variety1Total['total_grams']);
        $this->assertEquals(408.24, $variety2Total['total_grams']);
        $this->assertEquals(272.16, $variety3Total['total_grams']);

        // Check summary totals
        $this->assertEquals(3, $results['summary']['total_varieties']);
        $this->assertEquals(2, $results['summary']['total_items']);
        $this->assertEquals(1360.8, $results['summary']['total_grams']);

        // Verify item breakdown
        $this->assertCount(2, $results['item_breakdown']);
        
        foreach ($results['item_breakdown'] as $item) {
            $this->assertEquals('mix', $item['type']);
            $this->assertCount(3, $item['varieties']); // All should have 3 varieties
        }
    }

    /** @test */
    public function it_maintains_precision_with_fractional_percentages()
    {
        $category = Category::factory()->create(['name' => 'Precision Mix']);

        // Create varieties
        $variety1 = MasterSeedCatalog::create([
            'common_name' => 'Precision 1',
            'cultivar_name' => 'Test',
            'category' => 'test',
            'is_active' => true,
        ]);

        $variety2 = MasterSeedCatalog::create([
            'common_name' => 'Precision 2',
            'cultivar_name' => 'Test',
            'category' => 'test',
            'is_active' => true,
        ]);

        $variety3 = MasterSeedCatalog::create([
            'common_name' => 'Precision 3',
            'cultivar_name' => 'Test',
            'category' => 'test',
            'is_active' => true,
        ]);

        // Create product mix with fractional percentages
        $productMix = ProductMix::create([
            'name' => 'Precision Mix',
            'description' => 'Mix with fractional percentages',
            'is_active' => true,
        ]);

        // Use fractional percentages that add to 100%
        $productMix->masterSeedCatalogs()->attach($variety1->id, ['percentage' => 33.33]);
        $productMix->masterSeedCatalogs()->attach($variety2->id, ['percentage' => 33.34]);
        $productMix->masterSeedCatalogs()->attach($variety3->id, ['percentage' => 33.33]);

        // Verify the mix validates correctly (allowing for small floating point differences)
        $this->assertTrue($productMix->validatePercentages());

        $mixProduct = Product::create([
            'name' => 'Precision Mix Product',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Precision Pack',
            'price' => 15.00,
            'fill_weight' => 300.0, // Use round number for easy calculation
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should have 3 varieties
        $this->assertCount(3, $results['variety_totals']);

        // Calculate expected weights (300g total):
        // Variety1: 300 * 0.3333 = 99.99g
        // Variety2: 300 * 0.3334 = 100.02g  
        // Variety3: 300 * 0.3333 = 99.99g

        $variety1Total = collect($results['variety_totals'])
            ->where('variety_id', $variety1->id)
            ->first();
        $variety2Total = collect($results['variety_totals'])
            ->where('variety_id', $variety2->id)
            ->first();
        $variety3Total = collect($results['variety_totals'])
            ->where('variety_id', $variety3->id)
            ->first();

        // Check that results are properly rounded to 2 decimal places
        $this->assertEquals(99.99, $variety1Total['total_grams']);
        $this->assertEquals(100.02, $variety2Total['total_grams']);
        $this->assertEquals(99.99, $variety3Total['total_grams']);

        // Total should equal the original weight
        $totalCalculated = $variety1Total['total_grams'] + 
                         $variety2Total['total_grams'] + 
                         $variety3Total['total_grams'];
        
        $this->assertEquals(300.0, $totalCalculated);
    }
}
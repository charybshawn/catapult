<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Filament\Resources\OrderSimulatorResource\Services\OrderCalculationService;
use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductMix;
use App\Models\MasterSeedCatalog;
use App\Models\Category;
use App\Models\PackagingType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\Resources\OrderSimulatorResource\Pages\ManageOrderSimulator;

class OrderSimulatorBulkPricingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected OrderCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->service = new OrderCalculationService();
    }

    /** @test */
    public function it_excludes_wholesale_pricing_variations_from_display()
    {
        $category = Category::factory()->create(['name' => 'Wholesale Filter Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Wholesale Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Wholesale Filter Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create various pricing variations
        $retailVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Retail Standard',
            'price' => 10.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $wholesaleVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Wholesale Container',
            'price' => 8.00,
            'fill_weight_grams' => 226.8,
            'pricing_type' => 'wholesale',
            'is_active' => true,
        ]);

        $bulkVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk Pack',
            'price' => 30.00,
            'fill_weight_grams' => 453.6,
            'pricing_type' => 'bulk',
            'is_active' => true,
        ]);

        // Create wholesale variations with different name patterns
        $wholesalePatterns = [
            'Standard Wholesale Pack',
            'wholesale container small',
            'Premium Wholesale Option',
            'WHOLESALE BULK PACK',
        ];

        foreach ($wholesalePatterns as $name) {
            PriceVariation::create([
                'product_id' => $product->id,
                'name' => $name,
                'price' => 7.50,
                'fill_weight_grams' => 226.8,
                'pricing_type' => 'retail', // Even with retail type, should be filtered by name
                'is_active' => true,
            ]);
        }

        $component = Livewire::test(ManageOrderSimulator::class);

        // Should see retail and bulk variations
        $component->assertSeeText('Retail Standard');
        $component->assertSeeText('Bulk Pack');
        $component->assertSeeText('$10.00');
        $component->assertSeeText('$30.00');

        // Should NOT see any wholesale variations (filtered by name pattern)
        $component->assertDontSeeText('Wholesale Container');
        $component->assertDontSeeText('Standard Wholesale Pack');
        $component->assertDontSeeText('wholesale container small');
        $component->assertDontSeeText('Premium Wholesale Option');
        $component->assertDontSeeText('WHOLESALE BULK PACK');
        
        // Should not see the wholesale price
        $component->assertDontSeeText('$8.00');
        $component->assertDontSeeText('$7.50');
    }

    /** @test */
    public function it_excludes_live_tray_variations_from_display()
    {
        $category = Category::factory()->create(['name' => 'Live Tray Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Live Tray Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Live Tray Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create standard retail variation
        $retailVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Standard Container',
            'price' => 12.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        // Create live tray variations with different name patterns
        $liveTrayPatterns = [
            'Live Tray',
            'live tray small',
            'Premium Live Tray',
            'LIVE TRAY LARGE',
            'Micro Live Tray',
            'Live Tray - Premium',
        ];

        foreach ($liveTrayPatterns as $name) {
            PriceVariation::create([
                'product_id' => $product->id,
                'name' => $name,
                'price' => 25.00,
                'fill_weight_grams' => null, // Live trays often don't have fill weight
                'pricing_type' => 'retail',
                'is_active' => true,
            ]);
        }

        $component = Livewire::test(ManageOrderSimulator::class);

        // Should see the standard retail variation
        $component->assertSeeText('Standard Container');
        $component->assertSeeText('$12.00');

        // Should NOT see any live tray variations
        foreach ($liveTrayPatterns as $pattern) {
            $component->assertDontSeeText($pattern);
        }
        
        // Should not see live tray price
        $component->assertDontSeeText('$25.00');
    }

    /** @test */
    public function it_handles_bulk_pricing_tiers_correctly()
    {
        $category = Category::factory()->create(['name' => 'Bulk Pricing Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Bulk Pricing Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Bulk Pricing Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create different bulk pricing tiers
        $smallBulkVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - 1lb',
            'price' => 20.00,
            'fill_weight_grams' => 453.6, // 1 pound
            'pricing_type' => 'bulk',
            'pricing_unit' => 'per_lb',
            'is_active' => true,
        ]);

        $mediumBulkVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - 5lb',
            'price' => 90.00, // Better price per pound
            'fill_weight_grams' => 2268.0, // 5 pounds
            'pricing_type' => 'bulk',
            'pricing_unit' => 'per_lb',
            'is_active' => true,
        ]);

        $largeBulkVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - 10lb',
            'price' => 170.00, // Even better price per pound
            'fill_weight_grams' => 4536.0, // 10 pounds
            'pricing_type' => 'bulk',
            'pricing_unit' => 'per_lb',
            'is_active' => true,
        ]);

        // Test order calculation with different bulk quantities
        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $smallBulkVariation->id,
                'quantity' => 2, // 2 x 1lb = 2 lbs total
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $mediumBulkVariation->id,
                'quantity' => 1, // 1 x 5lb = 5 lbs total
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $largeBulkVariation->id,
                'quantity' => 1, // 1 x 10lb = 10 lbs total
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should have 1 variety total (all same variety)
        $this->assertCount(1, $results['variety_totals']);

        $varietyTotal = $results['variety_totals'][0];
        
        // Total weight: (2 * 453.6) + 2268.0 + 4536.0 = 907.2 + 2268.0 + 4536.0 = 7,711.2g
        $expectedTotal = (2 * 453.6) + 2268.0 + 4536.0;
        $this->assertEquals($expectedTotal, $varietyTotal['total_grams']);

        // Should have 3 different bulk entries
        $this->assertCount(3, $varietyTotal['products']);
        
        // Check item breakdown
        $this->assertCount(3, $results['item_breakdown']);

        // Verify each bulk tier is correctly represented
        $smallBulkItem = collect($results['item_breakdown'])
            ->where('package_size', 'Bulk - 1lb')
            ->first();
        $this->assertEquals(2, $smallBulkItem['quantity']);
        $this->assertEquals(907.2, $smallBulkItem['total_grams']);

        $mediumBulkItem = collect($results['item_breakdown'])
            ->where('package_size', 'Bulk - 5lb')
            ->first();
        $this->assertEquals(1, $mediumBulkItem['quantity']);
        $this->assertEquals(2268.0, $mediumBulkItem['total_grams']);

        $largeBulkItem = collect($results['item_breakdown'])
            ->where('package_size', 'Bulk - 10lb')
            ->first();
        $this->assertEquals(1, $largeBulkItem['quantity']);
        $this->assertEquals(4536.0, $largeBulkItem['total_grams']);
    }

    /** @test */
    public function it_calculates_bulk_mix_products_correctly()
    {
        $category = Category::factory()->create(['name' => 'Bulk Mix Category']);

        // Create varieties for mix
        $variety1 = MasterSeedCatalog::create([
            'common_name' => 'Bulk Mix Component 1',
            'cultivar_name' => 'First',
            'category' => 'test',
            'is_active' => true,
        ]);

        $variety2 = MasterSeedCatalog::create([
            'common_name' => 'Bulk Mix Component 2',
            'cultivar_name' => 'Second',
            'category' => 'test',
            'is_active' => true,
        ]);

        // Create product mix
        $productMix = ProductMix::create([
            'name' => 'Bulk Test Mix',
            'description' => 'Mix for bulk pricing testing',
            'is_active' => true,
        ]);

        $productMix->masterSeedCatalogs()->attach($variety1->id, ['percentage' => 75]);
        $productMix->masterSeedCatalogs()->attach($variety2->id, ['percentage' => 25]);

        $mixProduct = Product::create([
            'name' => 'Bulk Mix Product',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        // Create bulk pricing tiers for the mix
        $smallBulkMixVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Mix Bulk - 2lb',
            'price' => 35.00,
            'fill_weight_grams' => 907.2, // 2 pounds
            'pricing_type' => 'bulk',
            'is_active' => true,
        ]);

        $largeBulkMixVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Mix Bulk - 5lb',
            'price' => 80.00,
            'fill_weight_grams' => 2268.0, // 5 pounds
            'pricing_type' => 'bulk',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $smallBulkMixVariation->id,
                'quantity' => 3, // 3 x 2lb = 6 lbs total
            ],
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $largeBulkMixVariation->id,
                'quantity' => 2, // 2 x 5lb = 10 lbs total
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should have 2 varieties (from the mix)
        $this->assertCount(2, $results['variety_totals']);

        // Calculate expected totals:
        // Small bulk: 3 * 907.2g = 2,721.6g
        // Large bulk: 2 * 2268.0g = 4,536.0g
        // Total mix weight: 2,721.6 + 4,536.0 = 7,257.6g
        
        // Variety1 (75%): 7,257.6 * 0.75 = 5,443.2g
        // Variety2 (25%): 7,257.6 * 0.25 = 1,814.4g

        $variety1Total = collect($results['variety_totals'])
            ->where('variety_id', $variety1->id)
            ->first();
        $variety2Total = collect($results['variety_totals'])
            ->where('variety_id', $variety2->id)
            ->first();

        $this->assertEquals(5443.2, $variety1Total['total_grams']);
        $this->assertEquals(1814.4, $variety2Total['total_grams']);

        // Check that both bulk tiers are represented in each variety's products
        $this->assertCount(2, $variety1Total['products']);
        $this->assertCount(2, $variety2Total['products']);

        // Verify summary
        $this->assertEquals(7257.6, $results['summary']['total_grams']);
        $this->assertEquals(2, $results['summary']['total_items']);
        $this->assertEquals(2, $results['summary']['total_varieties']);
    }

    /** @test */
    public function it_handles_weight_based_pricing_units_in_bulk()
    {
        $category = Category::factory()->create(['name' => 'Weight Unit Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Weight Unit Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Weight Unit Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create bulk variations with different weight units
        $gramsBulkVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - 500g',
            'price' => 15.00,
            'fill_weight_grams' => 500.0,
            'pricing_unit' => 'per_g',
            'is_active' => true,
        ]);

        $kilogramsBulkVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - 2kg',
            'price' => 55.00,
            'fill_weight_grams' => 2000.0,
            'pricing_unit' => 'per_kg',
            'is_active' => true,
        ]);

        $poundsBulkVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - 5lb',
            'price' => 120.00,
            'fill_weight_grams' => 2268.0, // 5 * 453.6
            'pricing_unit' => 'per_lb',
            'is_active' => true,
        ]);

        // Test unit conversion methods
        $this->assertTrue($gramsBulkVariation->isSoldByWeight());
        $this->assertTrue($kilogramsBulkVariation->isSoldByWeight());
        $this->assertTrue($poundsBulkVariation->isSoldByWeight());

        // Test conversion factors
        $this->assertEquals(1.0, $gramsBulkVariation->getUnitToGramsConversionFactor());
        $this->assertEquals(1000.0, $kilogramsBulkVariation->getUnitToGramsConversionFactor());
        $this->assertEquals(453.592, $poundsBulkVariation->getUnitToGramsConversionFactor());

        // Test display units
        $this->assertEquals('grams', $gramsBulkVariation->getDisplayUnit());
        $this->assertEquals('kg', $kilogramsBulkVariation->getDisplayUnit());
        $this->assertEquals('lbs', $poundsBulkVariation->getDisplayUnit());

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $gramsBulkVariation->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $kilogramsBulkVariation->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $poundsBulkVariation->id,
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // All should aggregate to one variety
        $this->assertCount(1, $results['variety_totals']);

        $varietyTotal = $results['variety_totals'][0];
        
        // Total weight: (2 * 500) + 2000 + 2268 = 1000 + 2000 + 2268 = 5,268g
        $expectedTotal = (2 * 500) + 2000 + 2268;
        $this->assertEquals($expectedTotal, $varietyTotal['total_grams']);

        // Should have 3 different bulk entries with different units
        $this->assertCount(3, $varietyTotal['products']);
        $this->assertCount(3, $results['item_breakdown']);
    }

    /** @test */
    public function it_displays_bulk_pricing_correctly_in_ui()
    {
        $category = Category::factory()->create(['name' => 'UI Bulk Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'UI Bulk Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'UI Bulk Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create retail and bulk variations
        $retailVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Retail - 4oz Container',
            'price' => 8.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $bulkVariation1 = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - 1lb',
            'price' => 25.00,
            'fill_weight_grams' => 453.6,
            'pricing_type' => 'bulk',
            'is_active' => true,
        ]);

        $bulkVariation2 = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Bulk - 5lb',
            'price' => 110.00,
            'fill_weight_grams' => 2268.0,
            'pricing_type' => 'bulk',
            'is_active' => true,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        // Should display all variations (retail and bulk are both allowed)
        $component->assertSeeText('UI Bulk Test Product');
        $component->assertSeeText('Retail - 4oz Container');
        $component->assertSeeText('Bulk - 1lb');
        $component->assertSeeText('Bulk - 5lb');

        // Should display prices
        $component->assertSeeText('$8.00');
        $component->assertSeeText('$25.00');
        $component->assertSeeText('$110.00');

        // Should show package information with weights
        $component->assertSeeText('(113.4g)');
        $component->assertSeeText('(453.6g)');
        $component->assertSeeText('(2268g)');

        // Test quantity input and calculation
        $retailCompositeId = $product->id . '_' . $retailVariation->id;
        $bulk1CompositeId = $product->id . '_' . $bulkVariation1->id;
        $bulk2CompositeId = $product->id . '_' . $bulkVariation2->id;

        // Set quantities for different pricing tiers
        $component->set('quantities.' . $retailCompositeId, 5);
        $component->set('quantities.' . $bulk1CompositeId, 2);
        $component->set('quantities.' . $bulk2CompositeId, 1);

        // Calculate
        $component->call('calculate');

        $component->assertNotified('Calculation Complete');

        // Check that session has results with all pricing tiers
        $results = \Illuminate\Support\Facades\Session::get('order_simulator_results');
        $this->assertNotNull($results);
        
        // Should have 3 items in breakdown (one for each pricing tier)
        $this->assertCount(3, $results['item_breakdown']);
        
        // Total weight: (5 * 113.4) + (2 * 453.6) + (1 * 2268) = 567 + 907.2 + 2268 = 3,742.2g
        $expectedTotal = (5 * 113.4) + (2 * 453.6) + (1 * 2268);
        $this->assertEquals($expectedTotal, $results['summary']['total_grams']);
    }

    /** @test */
    public function it_prevents_live_tray_calculations_with_null_weights()
    {
        // Even though live trays are filtered from the UI, test service robustness
        $category = Category::factory()->create(['name' => 'Live Tray Service Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Live Tray Service Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Live Tray Service Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create a live tray variation (hypothetically, if it got through filtering)
        $liveTrayVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Live Tray - Large',
            'price' => 30.00,
            'fill_weight_grams' => null, // Live trays typically don't have fill weight
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $liveTrayVariation->id,
                'quantity' => 2,
            ]
        ];

        // Service should handle null fill_weight gracefully
        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertCount(1, $results['variety_totals']);
        
        $varietyTotal = $results['variety_totals'][0];
        $this->assertEquals(0, $varietyTotal['total_grams']); // Should be 0 due to null fill_weight

        // Item breakdown should still show the item
        $this->assertCount(1, $results['item_breakdown']);
        $item = $results['item_breakdown'][0];
        $this->assertEquals('Live Tray Service Product', $item['product_name']);
        $this->assertEquals('Live Tray - Large', $item['package_size']);
        $this->assertEquals(2, $item['quantity']);
        $this->assertEquals(0, $item['fill_weight_grams']);
        $this->assertEquals(0, $item['total_grams']);
    }

    /** @test */
    public function it_handles_mixed_bulk_and_retail_pricing_scenarios()
    {
        $category = Category::factory()->create(['name' => 'Mixed Pricing Category']);

        // Create single variety and mix for comprehensive testing
        $singleVariety = MasterSeedCatalog::create([
            'common_name' => 'Single Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $mixVariety1 = MasterSeedCatalog::create([
            'common_name' => 'Mix Component 1',
            'cultivar_name' => 'First',
            'category' => 'test',
            'is_active' => true,
        ]);

        $mixVariety2 = MasterSeedCatalog::create([
            'common_name' => 'Mix Component 2',
            'cultivar_name' => 'Second',
            'category' => 'test',
            'is_active' => true,
        ]);

        // Create single product
        $singleProduct = Product::create([
            'name' => 'Single Variety Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $singleVariety->id,
        ]);

        // Create mix product
        $productMix = ProductMix::create([
            'name' => 'Mixed Pricing Mix',
            'description' => 'Mix for pricing testing',
            'is_active' => true,
        ]);

        $productMix->masterSeedCatalogs()->attach($mixVariety1->id, ['percentage' => 60]);
        $productMix->masterSeedCatalogs()->attach($mixVariety2->id, ['percentage' => 40]);

        $mixProduct = Product::create([
            'name' => 'Mix Product',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        // Create various pricing variations
        $singleRetailVariation = PriceVariation::create([
            'product_id' => $singleProduct->id,
            'name' => 'Single Retail - 4oz',
            'price' => 9.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $singleBulkVariation = PriceVariation::create([
            'product_id' => $singleProduct->id,
            'name' => 'Single Bulk - 1lb',
            'price' => 32.00,
            'fill_weight_grams' => 453.6,
            'pricing_type' => 'bulk',
            'is_active' => true,
        ]);

        $mixRetailVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Mix Retail - 8oz',
            'price' => 18.00,
            'fill_weight_grams' => 226.8,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $mixBulkVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Mix Bulk - 2lb',
            'price' => 65.00,
            'fill_weight_grams' => 907.2,
            'pricing_type' => 'bulk',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $singleProduct->id,
                'price_variation_id' => $singleRetailVariation->id,
                'quantity' => 4,
            ],
            [
                'product_id' => $singleProduct->id,
                'price_variation_id' => $singleBulkVariation->id,
                'quantity' => 1,
            ],
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $mixRetailVariation->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $mixBulkVariation->id,
                'quantity' => 1,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should have 3 varieties total (1 single + 2 from mix)
        $this->assertCount(3, $results['variety_totals']);

        // Calculate expected weights:
        // Single retail: 4 * 113.4 = 453.6g
        // Single bulk: 1 * 453.6 = 453.6g
        // Total single variety: 453.6 + 453.6 = 907.2g

        // Mix retail: 2 * 226.8 = 453.6g
        // Mix bulk: 1 * 907.2 = 907.2g
        // Total mix weight: 453.6 + 907.2 = 1,360.8g
        
        // Mix variety 1 (60%): 1,360.8 * 0.6 = 816.48g
        // Mix variety 2 (40%): 1,360.8 * 0.4 = 544.32g

        $singleVarietyTotal = collect($results['variety_totals'])
            ->where('variety_id', $singleVariety->id)
            ->first();
        $mixVariety1Total = collect($results['variety_totals'])
            ->where('variety_id', $mixVariety1->id)
            ->first();
        $mixVariety2Total = collect($results['variety_totals'])
            ->where('variety_id', $mixVariety2->id)
            ->first();

        $this->assertEquals(907.2, $singleVarietyTotal['total_grams']);
        $this->assertEquals(816.48, $mixVariety1Total['total_grams']);
        $this->assertEquals(544.32, $mixVariety2Total['total_grams']);

        // Should have 4 items in breakdown (one for each variation)
        $this->assertCount(4, $results['item_breakdown']);

        // Verify summary
        $totalExpected = 907.2 + 816.48 + 544.32; // 2,267.99g
        $this->assertEquals($totalExpected, $results['summary']['total_grams']);
        $this->assertEquals(4, $results['summary']['total_items']);
        $this->assertEquals(3, $results['summary']['total_varieties']);
    }
}
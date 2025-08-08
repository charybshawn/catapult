<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Filament\Resources\OrderSimulatorResource\Services\OrderCalculationService;
use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductMix;
use App\Models\MasterSeedCatalog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use App\Filament\Resources\OrderSimulatorResource\Pages\ManageOrderSimulator;

class OrderSimulatorEdgeCasesTest extends TestCase
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
    public function it_handles_extremely_large_quantities()
    {
        $category = Category::factory()->create(['name' => 'Large Quantity Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Large Quantity Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Large Quantity Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Large Pack',
            'price' => 10.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 999999, // Very large quantity
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertCount(1, $results['variety_totals']);
        
        $varietyTotal = $results['variety_totals'][0];
        $expectedTotal = 999999 * 113.4; // Should handle large numbers without overflow
        
        $this->assertEquals($expectedTotal, $varietyTotal['total_grams']);
        $this->assertIsFloat($varietyTotal['total_grams']);
        $this->assertGreaterThan(0, $varietyTotal['total_grams']);
    }

    /** @test */
    public function it_handles_extremely_small_fill_weights()
    {
        $category = Category::factory()->create(['name' => 'Small Weight Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Small Weight Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Small Weight Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Tiny Pack',
            'price' => 1.00,
            'fill_weight_grams' => 0.001, // Very small fill weight
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 1000, // Large quantity of tiny packages
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertCount(1, $results['variety_totals']);
        
        $varietyTotal = $results['variety_totals'][0];
        $expectedTotal = 1000 * 0.001; // Should equal 1.0g
        
        $this->assertEquals($expectedTotal, $varietyTotal['total_grams']);
        $this->assertGreaterThan(0, $varietyTotal['total_grams']);
    }

    /** @test */
    public function it_handles_zero_fill_weights_gracefully()
    {
        $category = Category::factory()->create(['name' => 'Zero Weight Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Zero Weight Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Zero Weight Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Zero Weight Pack',
            'price' => 10.00,
            'fill_weight_grams' => 0, // Zero fill weight
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 5,
            ]
        ];

        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertCount(1, $results['variety_totals']);
        
        $varietyTotal = $results['variety_totals'][0];
        $this->assertEquals(0, $varietyTotal['total_grams']); // Should be 0
        $this->assertEquals(0, $results['summary']['total_grams']);
    }

    /** @test */
    public function it_handles_null_fill_weights()
    {
        $category = Category::factory()->create(['name' => 'Null Weight Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Null Weight Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Null Weight Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Null Weight Pack',
            'price' => 10.00,
            'fill_weight_grams' => null, // Null fill weight
            'pricing_type' => 'retail',
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

        $this->assertCount(1, $results['variety_totals']);
        
        $varietyTotal = $results['variety_totals'][0];
        $this->assertEquals(0, $varietyTotal['total_grams']); // Should default to 0
    }

    /** @test */
    public function it_handles_product_mix_with_zero_percentage_components()
    {
        $category = Category::factory()->create(['name' => 'Zero Percentage Category']);

        $variety1 = MasterSeedCatalog::create([
            'common_name' => 'Normal Component',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $variety2 = MasterSeedCatalog::create([
            'common_name' => 'Zero Component',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $productMix = ProductMix::create([
            'name' => 'Zero Percentage Mix',
            'description' => 'Mix with zero percentage component',
            'is_active' => true,
        ]);

        // Attach varieties with one having zero percentage
        $productMix->masterSeedCatalogs()->attach($variety1->id, ['percentage' => 100]);
        $productMix->masterSeedCatalogs()->attach($variety2->id, ['percentage' => 0]);

        $mixProduct = Product::create([
            'name' => 'Zero Percentage Mix Product',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Zero Mix Pack',
            'price' => 15.00,
            'fill_weight_grams' => 200.0,
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

        // Should have 2 variety totals (including the zero one)
        $this->assertCount(2, $results['variety_totals']);

        $variety1Total = collect($results['variety_totals'])
            ->where('variety_id', $variety1->id)
            ->first();
        $variety2Total = collect($results['variety_totals'])
            ->where('variety_id', $variety2->id)
            ->first();

        $this->assertEquals(200.0, $variety1Total['total_grams']); // 100% of 200g
        $this->assertEquals(0.0, $variety2Total['total_grams']); // 0% of 200g
    }

    /** @test */
    public function it_handles_product_mix_with_missing_master_seed_catalogs()
    {
        $category = Category::factory()->create(['name' => 'Missing Catalog Category']);

        $variety = MasterSeedCatalog::create([
            'common_name' => 'Valid Component',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $productMix = ProductMix::create([
            'name' => 'Broken Mix',
            'description' => 'Mix with missing components',
            'is_active' => true,
        ]);

        // Attach one valid variety and one invalid ID
        $productMix->masterSeedCatalogs()->attach($variety->id, ['percentage' => 60]);
        $productMix->masterSeedCatalogs()->attach(999999, ['percentage' => 40]); // Non-existent ID

        $mixProduct = Product::create([
            'name' => 'Broken Mix Product',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Broken Mix Pack',
            'price' => 15.00,
            'fill_weight_grams' => 200.0,
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 1,
            ]
        ];

        // This should not throw an exception
        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should only have 1 variety total (the valid one)
        $this->assertCount(1, $results['variety_totals']);
        
        $varietyTotal = $results['variety_totals'][0];
        $this->assertEquals($variety->id, $varietyTotal['variety_id']);
        $this->assertEquals(120.0, $varietyTotal['total_grams']); // 60% of 200g
    }

    /** @test */
    public function it_handles_concurrent_session_modifications()
    {
        $category = Category::factory()->create(['name' => 'Concurrent Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Concurrent Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Concurrent Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Concurrent Pack',
            'price' => 10.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        $compositeId = $product->id . '_' . $priceVariation->id;

        // Set quantity in component
        $component->set('quantities.' . $compositeId, 3);

        // Simulate external modification to session (concurrent access)
        Session::put('order_simulator_quantities', ['different_key' => 5]);

        // The component should handle this gracefully
        $component->call('calculate');

        // Should show warning about no products selected (since session was cleared)
        $component->assertNotified('No Products Selected');
    }

    /** @test */
    public function it_handles_database_transaction_isolation()
    {
        $category = Category::factory()->create(['name' => 'Transaction Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Transaction Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Transaction Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Transaction Pack',
            'price' => 10.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        // Start calculation service
        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 2,
            ]
        ];

        // Deactivate the product mid-calculation (simulate concurrent modification)
        $product->update(['active' => false]);

        // Service should still complete with the data as it was when loaded
        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should still get results (service loads fresh data each time)
        $this->assertCount(1, $results['variety_totals']);
        $this->assertEquals(226.8, $results['summary']['total_grams']);
    }

    /** @test */
    public function it_validates_session_data_integrity()
    {
        $component = Livewire::test(ManageOrderSimulator::class);

        // Set malformed session data
        Session::put('order_simulator_quantities', 'not_an_array');
        Session::put('order_simulator_hidden_rows', 123);

        // Component should handle gracefully and reset to defaults
        $component->call('mount');

        $this->assertIsArray($component->get('quantities'));
        $this->assertIsArray($component->get('hiddenRows'));
        $this->assertEmpty($component->get('quantities'));
        $this->assertEmpty($component->get('hiddenRows'));
    }

    /** @test */
    public function it_handles_very_long_product_names_and_descriptions()
    {
        $category = Category::factory()->create(['name' => 'Long Name Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => str_repeat('Very Long Product Name With Many Words ', 10),
            'cultivar_name' => str_repeat('Cultivar ', 20),
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => str_repeat('Extremely Long Product Name That Exceeds Normal Expectations ', 5),
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => str_repeat('Very Long Variation Name ', 10),
            'price' => 10.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 1,
            ]
        ];

        // Should not throw exceptions or cause display issues
        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertCount(1, $results['variety_totals']);
        
        $varietyTotal = $results['variety_totals'][0];
        $this->assertIsString($varietyTotal['variety_name']);
        $this->assertGreaterThan(0, strlen($varietyTotal['variety_name']));

        $itemBreakdown = $results['item_breakdown'][0];
        $this->assertIsString($itemBreakdown['product_name']);
        $this->assertIsString($itemBreakdown['package_size']);
    }

    /** @test */
    public function it_handles_special_characters_in_names()
    {
        $category = Category::factory()->create(['name' => 'Special Char Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Variety with éspecial chars & symbols',
            'cultivar_name' => 'Cultivar™ with ® symbols',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Product with émojis 🌱 & symbols',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Pack with "quotes" & <tags>',
            'price' => 10.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
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

        $this->assertCount(1, $results['variety_totals']);
        
        $varietyTotal = $results['variety_totals'][0];
        $this->assertStringContainsString('éspecial', $varietyTotal['variety_name']);
        $this->assertStringContainsString('™', $varietyTotal['variety_name']);

        $itemBreakdown = $results['item_breakdown'][0];
        $this->assertStringContainsString('🌱', $itemBreakdown['product_name']);
        $this->assertStringContainsString('"quotes"', $itemBreakdown['package_size']);
    }

    /** @test */
    public function it_handles_memory_intensive_calculations()
    {
        $category = Category::factory()->create(['name' => 'Memory Test Category']);

        // Create many varieties for a large mix
        $varieties = [];
        for ($i = 1; $i <= 50; $i++) {
            $varieties[] = MasterSeedCatalog::create([
                'common_name' => "Memory Test Variety {$i}",
                'cultivar_name' => "Cultivar {$i}",
                'category' => 'test',
                'is_active' => true,
            ]);
        }

        // Create product mix with many components
        $productMix = ProductMix::create([
            'name' => 'Large Memory Mix',
            'description' => 'Mix with many components for memory testing',
            'is_active' => true,
        ]);

        // Each variety gets 2% (50 * 2% = 100%)
        foreach ($varieties as $variety) {
            $productMix->masterSeedCatalogs()->attach($variety->id, ['percentage' => 2.0]);
        }

        $mixProduct = Product::create([
            'name' => 'Large Memory Mix Product',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $mixProduct->id,
            'name' => 'Large Memory Pack',
            'price' => 50.00,
            'fill_weight_grams' => 1000.0, // 1kg
            'is_active' => true,
        ]);

        $orderItems = [
            [
                'product_id' => $mixProduct->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 10, // Large quantity
            ]
        ];

        // This should complete without running out of memory
        $results = $this->service->calculateVarietyRequirements($orderItems);

        $this->assertCount(50, $results['variety_totals']); // All 50 varieties
        $this->assertEquals(10000.0, $results['summary']['total_grams']); // 10 * 1000g

        // Each variety should have 2% of the total
        foreach ($results['variety_totals'] as $varietyTotal) {
            $this->assertEquals(200.0, $varietyTotal['total_grams']); // 2% of 10,000g
        }
    }

    /** @test */
    public function it_handles_circular_references_safely()
    {
        // Create a scenario that might cause circular references
        $category = Category::factory()->create(['name' => 'Circular Test Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Circular Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Circular Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Circular Pack',
            'price' => 10.00,
            'fill_weight_grams' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        // Create an order that references the same product multiple times
        $orderItems = [
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 3,
            ],
            // Same product/variation referenced again
            [
                'product_id' => $product->id,
                'price_variation_id' => $priceVariation->id,
                'quantity' => 1,
            ]
        ];

        // Should handle this without infinite loops or issues
        $results = $this->service->calculateVarietyRequirements($orderItems);

        // Should aggregate all quantities for the same variety
        $this->assertCount(1, $results['variety_totals']);
        $varietyTotal = $results['variety_totals'][0];
        
        // Total should be (2 + 3 + 1) * 113.4g = 680.4g
        $this->assertEquals(680.4, $varietyTotal['total_grams']);

        // But item breakdown should show 3 separate entries
        $this->assertCount(3, $results['item_breakdown']);
        $this->assertEquals(3, $results['summary']['total_items']);
    }
}
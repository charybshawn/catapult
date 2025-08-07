<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductMix;
use App\Models\MasterSeedCatalog;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use App\Filament\Resources\OrderSimulatorResource\Pages\ManageOrderSimulator;

class OrderSimulatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_displays_order_simulator_page()
    {
        $response = $this->get('/admin/order-simulator-resources');

        $response->assertStatus(200);
        $response->assertSee('Order Simulator');
    }

    /** @test */
    public function it_shows_active_retail_products_with_price_variations()
    {
        // Create category
        $category = Category::factory()->create(['name' => 'Test Category']);

        // Create variety
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Basil',
            'cultivar_name' => 'Genovese',
            'category' => 'herbs',
            'is_active' => true,
        ]);

        // Create active product
        $activeProduct = Product::create([
            'name' => 'Active Basil Seeds',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create inactive product (should not show)
        $inactiveProduct = Product::create([
            'name' => 'Inactive Basil Seeds',
            'active' => false,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create retail price variations
        $retailVariation = PriceVariation::create([
            'product_id' => $activeProduct->id,
            'name' => 'Retail - 4oz',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        // Create wholesale variation (should not show due to filter)
        $wholesaleVariation = PriceVariation::create([
            'product_id' => $activeProduct->id,
            'name' => 'Wholesale - 4oz',
            'price' => 8.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'wholesale',
            'is_active' => true,
        ]);

        // Create Live Tray variation (should not show due to filter)
        $liveTrayVariation = PriceVariation::create([
            'product_id' => $activeProduct->id,
            'name' => 'Live Tray',
            'price' => 15.00,
            'fill_weight' => null,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        // Create inactive variation (should not show)
        $inactiveVariation = PriceVariation::create([
            'product_id' => $activeProduct->id,
            'name' => 'Inactive - 4oz',
            'price' => 12.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => false,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        // Should see the active retail product
        $component->assertSeeText('Active Basil Seeds');
        $component->assertSeeText('Retail - 4oz');
        $component->assertSeeText('$10.00');

        // Should not see inactive product, wholesale, live tray, or inactive variations
        $component->assertDontSeeText('Inactive Basil Seeds');
        $component->assertDontSeeText('Wholesale - 4oz');
        $component->assertDontSeeText('Live Tray');
        $component->assertDontSeeText('Inactive - 4oz');
    }

    /** @test */
    public function it_filters_out_wholesale_and_live_tray_variations()
    {
        $category = Category::factory()->create(['name' => 'Filter Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Filter Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        // Create variations that should be filtered out
        $variations = [
            // Wholesale variations (case insensitive)
            ['name' => 'Wholesale Pack', 'should_show' => false],
            ['name' => 'wholesale container', 'should_show' => false],
            ['name' => 'Standard Wholesale', 'should_show' => false],
            
            // Live Tray variations (case insensitive)
            ['name' => 'Live Tray', 'should_show' => false],
            ['name' => 'live tray small', 'should_show' => false],
            ['name' => 'Premium Live Tray', 'should_show' => false],
            
            // Valid retail variations (should show)
            ['name' => 'Retail Pack', 'should_show' => true],
            ['name' => 'Standard Container', 'should_show' => true],
            ['name' => 'Bulk Container', 'should_show' => true],
        ];

        foreach ($variations as $variation) {
            PriceVariation::create([
                'product_id' => $product->id,
                'name' => $variation['name'],
                'price' => 10.00,
                'fill_weight' => 113.4,
                'pricing_type' => 'retail',
                'is_active' => true,
            ]);
        }

        $component = Livewire::test(ManageOrderSimulator::class);

        foreach ($variations as $variation) {
            if ($variation['should_show']) {
                $component->assertSeeText($variation['name']);
            } else {
                $component->assertDontSeeText($variation['name']);
            }
        }
    }

    /** @test */
    public function it_allows_updating_quantities_for_products()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Quantity Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Quantity Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Quantity Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Test Pack',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        // Generate the composite key as the component does
        $compositeKey = $product->id . '_' . $priceVariation->id;

        // Initially quantity should be 0
        $this->assertEquals(0, $component->get('quantities')[$compositeKey] ?? 0);

        // Update quantity using the table column update method
        $record = (object) [
            'product_id' => $product->id,
            'variation_id' => $priceVariation->id,
        ];
        
        // Simulate updating the quantity through the TextInputColumn
        $component->set('quantities.' . $compositeKey, 5);

        // Verify quantity is updated
        $this->assertEquals(5, $component->get('quantities')[$compositeKey]);

        // Verify it's stored in session
        $this->assertEquals(5, Session::get('order_simulator_quantities')[$compositeKey]);
    }

    /** @test */
    public function it_persists_quantities_in_session()
    {
        // Set initial quantities in session
        $quantities = ['1_1' => 3, '2_2' => 5];
        Session::put('order_simulator_quantities', $quantities);

        $component = Livewire::test(ManageOrderSimulator::class);

        // Component should load quantities from session
        $this->assertEquals($quantities, $component->get('quantities'));
    }

    /** @test */
    public function it_can_hide_and_restore_rows()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Hide Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Hide Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Hide Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Hide Pack',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        // Initially should see the product
        $component->assertSeeText('Hide Test Product');

        // Hide the row
        $compositeId = $product->id . '_' . $priceVariation->id;
        $component->call('hideRow', $compositeId);

        // Should not see the product anymore
        $component->assertDontSeeText('Hide Test Product');

        // Should see hidden count
        $component->assertSeeText('Show Hidden (1)');

        // Verify it's in hidden rows
        $hiddenRows = $component->get('hiddenRows');
        $this->assertArrayHasKey($compositeId, $hiddenRows);

        // Restore the row
        $component->call('showHiddenItem', $compositeId);

        // Should see the product again
        $component->assertSeeText('Hide Test Product');

        // Hidden count should be gone
        $component->assertDontSeeText('Show Hidden (1)');
    }

    /** @test */
    public function it_removes_quantity_when_hiding_row()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Remove Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Remove Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Remove Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Remove Pack',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        $compositeId = $product->id . '_' . $priceVariation->id;

        // Set a quantity first
        $component->set('quantities.' . $compositeId, 5);
        $this->assertEquals(5, $component->get('quantities')[$compositeId]);

        // Hide the row
        $component->call('hideRow', $compositeId);

        // Quantity should be removed
        $quantities = $component->get('quantities');
        $this->assertArrayNotHasKey($compositeId, $quantities);
    }

    /** @test */
    public function it_calculates_requirements_successfully()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Calc Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Calc Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Calc Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Calc Pack',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        $compositeId = $product->id . '_' . $priceVariation->id;

        // Set quantity
        $component->set('quantities.' . $compositeId, 3);

        // Call calculate
        $component->call('calculate');

        // Should see success notification
        $component->assertNotified('Calculation Complete');

        // Check that results are stored in session
        $results = Session::get('order_simulator_results');
        $this->assertNotNull($results);
        $this->assertArrayHasKey('variety_totals', $results);
        $this->assertArrayHasKey('summary', $results);
    }

    /** @test */
    public function it_shows_warning_when_no_products_selected()
    {
        $component = Livewire::test(ManageOrderSimulator::class);

        // Call calculate with no quantities set
        $component->call('calculate');

        // Should see warning notification
        $component->assertNotified('No Products Selected');
    }

    /** @test */
    public function it_ignores_zero_quantities_in_calculation()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Zero Category']);
        $variety = MasterSeedCatalog::create([
            'common_name' => 'Zero Test',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Zero Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ]);

        $priceVariation = PriceVariation::create([
            'product_id' => $product->id,
            'name' => 'Zero Pack',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        $compositeId = $product->id . '_' . $priceVariation->id;

        // Set zero quantity
        $component->set('quantities.' . $compositeId, 0);

        // Call calculate
        $component->call('calculate');

        // Should see warning notification (no active quantities)
        $component->assertNotified('No Products Selected');
    }

    /** @test */
    public function it_clears_all_data_successfully()
    {
        // Create test data and set some state
        Session::put('order_simulator_quantities', ['1_1' => 5]);
        Session::put('order_simulator_hidden_rows', ['2_2' => ['product_name' => 'Test']]);
        Session::put('order_simulator_results', ['test' => 'data']);

        $component = Livewire::test(ManageOrderSimulator::class);

        // Set component state
        $component->set('quantities', ['1_1' => 5]);
        $component->set('hiddenRows', ['2_2' => ['product_name' => 'Test']]);
        $component->set('showHiddenPanel', true);

        // Call clear
        $component->call('clear');

        // Should see success notification
        $component->assertNotified('Cleared');

        // Component state should be cleared
        $this->assertEmpty($component->get('quantities'));
        $this->assertEmpty($component->get('hiddenRows'));
        $this->assertFalse($component->get('showHiddenPanel'));

        // Session should be cleared
        $this->assertNull(Session::get('order_simulator_quantities'));
        $this->assertNull(Session::get('order_simulator_hidden_rows'));
        $this->assertNull(Session::get('order_simulator_results'));
    }

    /** @test */
    public function it_handles_invalid_composite_ids_gracefully()
    {
        $component = Livewire::test(ManageOrderSimulator::class);

        // Try to hide row with invalid format
        $component->call('hideRow', 'invalid_format_id');

        // Should show error notification
        $component->assertNotified('Error');
    }

    /** @test */
    public function it_handles_non_existent_products_gracefully()
    {
        $component = Livewire::test(ManageOrderSimulator::class);

        // Try to hide row with non-existent product/variation
        $component->call('hideRow', '999_999');

        // Should show error notification
        $component->assertNotified('Error');
    }

    /** @test */
    public function it_toggles_hidden_panel_visibility()
    {
        $component = Livewire::test(ManageOrderSimulator::class);

        // Initially panel should be hidden
        $this->assertFalse($component->get('showHiddenPanel'));

        // Toggle panel
        $component->call('toggleHiddenPanel');
        $this->assertTrue($component->get('showHiddenPanel'));

        // Toggle again
        $component->call('toggleHiddenPanel');
        $this->assertFalse($component->get('showHiddenPanel'));

        // Check session persistence
        $this->assertFalse(Session::get('order_simulator_panel_open'));
    }

    /** @test */
    public function it_restores_all_hidden_rows()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Restore Category']);
        $variety1 = MasterSeedCatalog::create([
            'common_name' => 'Restore Test 1',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);
        $variety2 = MasterSeedCatalog::create([
            'common_name' => 'Restore Test 2',
            'cultivar_name' => 'Variety',
            'category' => 'test',
            'is_active' => true,
        ]);

        $product1 = Product::create([
            'name' => 'Restore Test Product 1',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety1->id,
        ]);

        $product2 = Product::create([
            'name' => 'Restore Test Product 2',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety2->id,
        ]);

        $priceVariation1 = PriceVariation::create([
            'product_id' => $product1->id,
            'name' => 'Pack 1',
            'price' => 10.00,
            'fill_weight' => 113.4,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $priceVariation2 = PriceVariation::create([
            'product_id' => $product2->id,
            'name' => 'Pack 2',
            'price' => 15.00,
            'fill_weight' => 226.8,
            'pricing_type' => 'retail',
            'is_active' => true,
        ]);

        $component = Livewire::test(ManageOrderSimulator::class);

        // Hide both rows
        $compositeId1 = $product1->id . '_' . $priceVariation1->id;
        $compositeId2 = $product2->id . '_' . $priceVariation2->id;
        
        $component->call('hideRow', $compositeId1);
        $component->call('hideRow', $compositeId2);

        // Should show count of 2
        $component->assertSeeText('Show Hidden (2)');

        // Restore all hidden rows
        $component->call('showHiddenRows');

        // Should see both products again
        $component->assertSeeText('Restore Test Product 1');
        $component->assertSeeText('Restore Test Product 2');

        // Hidden count should be gone
        $component->assertDontSeeText('Show Hidden');

        // Hidden rows should be empty
        $this->assertEmpty($component->get('hiddenRows'));
    }
}
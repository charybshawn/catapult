<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\Consumable;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\SeedEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TraitTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed required lookup data
        $this->seed(\Database\Seeders\Lookup\SupplierTypeSeeder::class);
    }

    /**
     * Test HasActiveStatus trait functionality.
     */
    public function test_has_active_status_trait()
    {
        // Test with Consumable model
        $consumable = Consumable::factory()->create(['is_active' => true]);
        
        $this->assertTrue($consumable->isActive());
        $this->assertFalse($consumable->isInactive());
        $this->assertEquals('Active', $consumable->status);
        
        // Test deactivate method
        $consumable->deactivate();
        $this->assertFalse($consumable->isActive());
        $this->assertTrue($consumable->isInactive());
        $this->assertEquals('Inactive', $consumable->status);
        
        // Test activate method
        $consumable->activate();
        $this->assertTrue($consumable->isActive());
        
        // Test toggleActive method
        $consumable->toggleActive();
        $this->assertFalse($consumable->isActive());
        
        // Test scopes
        $activeCount = Consumable::active()->count();
        $inactiveCount = Consumable::inactive()->count();
        $this->assertIsInt($activeCount);
        $this->assertIsInt($inactiveCount);
        
        // Test with Product model (uses 'active' field)
        $product = Product::factory()->create(['active' => true]);
        $this->assertTrue($product->isActive());
        $this->assertEquals('Active', $product->status);
    }

    /**
     * Test HasSupplier trait functionality.
     */
    public function test_has_supplier_trait()
    {
        $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);
        $consumable = Consumable::factory()->create(['supplier_id' => $supplier->id]);
        
        // Test relationship
        $this->assertInstanceOf(Supplier::class, $consumable->supplier);
        $this->assertEquals('Test Supplier', $consumable->supplier_name);
        
        // Test hasSupplier method
        $this->assertTrue($consumable->hasSupplier());
        
        // Test isFromSupplier method
        $this->assertTrue($consumable->isFromSupplier($supplier));
        $this->assertTrue($consumable->isFromSupplier($supplier->id));
        
        // Test scopes
        $fromSupplier = Consumable::fromSupplier($supplier)->get();
        $this->assertTrue($fromSupplier->contains($consumable));
        
        // Test setSupplierByName
        $seedEntry = new SeedEntry();
        $seedEntry->setSupplierByName('New Supplier');
        $this->assertNotNull($seedEntry->supplier_id);
        
        $newSupplier = Supplier::where('name', 'New Supplier')->first();
        $this->assertNotNull($newSupplier);
        $this->assertEquals($newSupplier->id, $seedEntry->supplier_id);
    }

    /**
     * Test HasCostInformation trait functionality.
     */
    public function test_has_cost_information_trait()
    {
        $consumable = Consumable::factory()->create([
            'cost_per_unit' => 10.50
        ]);
        
        // Test getCost method
        $this->assertEquals(10.50, $consumable->getCost());
        
        // Test hasCost method
        $this->assertTrue($consumable->hasCost());
        
        // Test formatted cost
        $this->assertEquals('$10.50', $consumable->formatted_cost);
        
        // Test calculateTotalValue
        $consumable->initial_stock = 5;
        $totalValue = $consumable->calculateTotalValue('initial_stock');
        $this->assertEquals(52.50, $totalValue);
        
        // Test scopes
        $withCost = Consumable::withCost()->get();
        $this->assertTrue($withCost->contains($consumable));
        
        // Test cost range scope
        $inRange = Consumable::costBetween(5, 15)->get();
        $this->assertTrue($inRange->contains($consumable));
    }

    /**
     * Test HasTimestamps trait functionality.
     */
    public function test_has_timestamps_trait()
    {
        $recipe = Recipe::factory()->create();
        
        // Test age calculations
        $this->assertIsInt($recipe->age_in_days);
        $this->assertIsString($recipe->time_since_creation);
        $this->assertIsString($recipe->time_since_update);
        
        // Test date checks
        $this->assertIsBool($recipe->wasCreatedToday());
        $this->assertIsBool($recipe->wasUpdatedToday());
        
        // Test scopes
        $todayRecipes = Recipe::createdToday()->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $todayRecipes);
        
        $recentRecipes = Recipe::createdInLastDays(7)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $recentRecipes);
        
        // Test ordering scopes
        $latest = Recipe::latest()->first();
        $oldest = Recipe::oldest()->first();
        
        if (Recipe::count() > 1) {
            $this->assertTrue($latest->created_at->gte($oldest->created_at));
        }
    }
}
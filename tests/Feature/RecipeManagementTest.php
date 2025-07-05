<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Recipe;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\SeedEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required lookup data - use firstOrCreate to avoid duplicates
        ConsumableType::firstOrCreate(
            ['code' => 'seed'],
            ['name' => 'Seeds', 'is_active' => true]
        );
        
        ConsumableType::firstOrCreate(
            ['code' => 'soil'],
            ['name' => 'Soil', 'is_active' => true]
        );
        
        ConsumableUnit::firstOrCreate(
            ['code' => 'unit'],
            ['name' => 'Unit', 'symbol' => 'unit', 'is_active' => true]
        );
        
        SupplierType::firstOrCreate(
            ['code' => 'soil'],
            ['name' => 'Soil Supplier', 'is_active' => true]
        );
    }

    /** @test */
    public function recipe_can_be_created_with_lot_based_system(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create seed consumable with lot
        $seedConsumable = Consumable::factory()->create([
            'name' => 'Kale (Red Russian)',
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id,
            'lot_no' => 'KRR2025',
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'is_active' => true,
        ]);

        // Create soil consumable
        $soilConsumable = Consumable::factory()->create([
            'type' => 'soil',
            'name' => 'Pro-Mix HP',
            'initial_stock' => 10,
            'quantity_per_unit' => 107.0,
            'quantity_unit' => 'l',
            'is_active' => true,
        ]);

        $recipeData = [
            'name' => 'Kale (Red Russian) - 25G - 14 DTM - KRR2025',
            'common_name' => 'Kale',
            'cultivar_name' => 'Red Russian',
            'lot_number' => 'KRR2025',
            'soil_consumable_id' => $soilConsumable->id,
            'seed_density_grams_per_tray' => 25.0,
            'seed_density' => 25.0, // Legacy field
            'days_to_maturity' => 14.0,
            'germination_days' => 3.0,
            'blackout_days' => 2.0,
            'light_days' => 9.0,
            'seed_soak_hours' => 0,
            'expected_yield_grams' => 200.0,
            'buffer_percentage' => 10.0,
            'suspend_water_hours' => 12,
            'harvest_days' => 0, // Legacy field
            'is_active' => true,
            'notes' => 'Test recipe creation',
        ];

        $recipe = Recipe::create($recipeData);

        $this->assertInstanceOf(Recipe::class, $recipe);
        $this->assertEquals('Kale (Red Russian) - 25G - 14 DTM - KRR2025', $recipe->name);
        $this->assertEquals('KRR2025', $recipe->lot_number);
        $this->assertEquals('Kale', $recipe->common_name);
        $this->assertEquals('Red Russian', $recipe->cultivar_name);
        $this->assertEquals(25.0, $recipe->seed_density_grams_per_tray);
        $this->assertEquals(14.0, $recipe->days_to_maturity);
        $this->assertTrue($recipe->is_active);
    }

    /** @test */
    public function recipe_automatically_generates_name_from_components(): void
    {
        $recipe = Recipe::factory()->create([
            'lot_number' => 'TEST123',
            'seed_density_grams_per_tray' => 30.0,
            'days_to_maturity' => 12.0,
            'common_name' => null,
            'cultivar_name' => null,
            'name' => 'Temporary Name', // Will be overwritten by generateRecipeName
        ]);

        // Create matching consumable
        $seedTypeId = \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id;
        Consumable::factory()->create([
            'name' => 'Basil (Genovese)',
            'consumable_type_id' => $seedTypeId,
            'lot_no' => 'TEST123',
            'is_active' => true,
        ]);

        // Trigger name generation
        $recipe->generateRecipeName();
        $recipe->save();

        $this->assertEquals('Basil (Genovese) - 30G - 12 DTM - TEST123', $recipe->name);
        $this->assertEquals('Basil', $recipe->common_name);
        $this->assertEquals('Genovese', $recipe->cultivar_name);
    }

    /** @test */
    public function recipe_calculates_total_days_correctly(): void
    {
        $recipe = Recipe::factory()->create([
            'days_to_maturity' => 14.0,
            'germination_days' => 3.0,
            'blackout_days' => 2.0,
            'light_days' => 9.0,
        ]);

        // Should prefer days_to_maturity when set
        $this->assertEquals(14.0, $recipe->totalDays());

        // Test fallback to sum of stages when DTM is null
        $recipe->days_to_maturity = null;
        $this->assertEquals(14.0, $recipe->totalDays()); // 3 + 2 + 9
    }

    /** @test */
    public function recipe_calculates_effective_total_days_with_soak_time(): void
    {
        $recipe = Recipe::factory()->create([
            'days_to_maturity' => 14.0,
            'seed_soak_hours' => 12,
        ]);

        // Should include soak time: 0.5 days + 14 days = 14.5 days
        $this->assertEquals(14.5, $recipe->effectiveTotalDays());
    }

    /** @test */
    public function recipe_tracks_lot_inventory_integration(): void
    {
        // Create multiple consumables for the same lot
        $consumable1 = Consumable::factory()->create([
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id,
            'lot_no' => 'MULTI_LOT',
            'total_quantity' => 500.0,
            'consumed_quantity' => 100.0, // 400 available
            'is_active' => true,
        ]);

        $consumable2 = Consumable::factory()->create([
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id,
            'lot_no' => 'MULTI_LOT',
            'total_quantity' => 800.0,
            'consumed_quantity' => 200.0, // 600 available
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'MULTI_LOT',
        ]);

        // Test lot consumables relationship
        $lotConsumables = $recipe->lotConsumables();
        $this->assertCount(2, $lotConsumables);

        // Test available lot consumables
        $availableConsumables = $recipe->availableLotConsumables();
        $this->assertCount(2, $availableConsumables);

        // Test total lot quantity (400 + 600 = 1000)
        $this->assertEquals(1000.0, $recipe->getLotQuantity());

        // Test lot not depleted
        $this->assertFalse($recipe->isLotDepleted());

        // Test can execute with sufficient stock
        $this->assertTrue($recipe->canExecute(500.0));
        $this->assertTrue($recipe->canExecute(1000.0));
        $this->assertFalse($recipe->canExecute(1001.0));
    }

    /** @test */
    public function recipe_handles_depleted_lots_correctly(): void
    {
        // Create fully consumed consumables
        Consumable::factory()->create([
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id,
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 500.0,
            'consumed_quantity' => 500.0,
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'DEPLETED_LOT',
        ]);

        $this->assertTrue($recipe->isLotDepleted());
        $this->assertEquals(0.0, $recipe->getLotQuantity());
        $this->assertFalse($recipe->canExecute(1.0));
    }

    /** @test */
    public function recipe_can_be_manually_marked_as_depleted(): void
    {
        // Create recipe with available stock
        Consumable::factory()->create([
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id,
            'lot_no' => 'MANUAL_DEPLETE',
            'total_quantity' => 500.0,
            'consumed_quantity' => 100.0,
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'MANUAL_DEPLETE',
            'lot_depleted_at' => null,
        ]);

        // Initially not depleted
        $this->assertFalse($recipe->isLotDepleted());
        $this->assertNull($recipe->lot_depleted_at);

        // Manually mark as depleted
        $recipe->markLotDepleted();

        $this->assertNotNull($recipe->lot_depleted_at);
        $this->assertTrue($recipe->isLotDepleted());
        $this->assertInstanceOf(\Carbon\Carbon::class, $recipe->lot_depleted_at);
    }

    /** @test */
    public function recipe_relationships_work_correctly(): void
    {
        $seedEntry = SeedEntry::factory()->create();
        $supplier = Supplier::factory()->create();
        $soilConsumable = Consumable::factory()->create(['type' => 'soil']);

        $recipe = Recipe::factory()->create([
            'seed_entry_id' => $seedEntry->id,
            'supplier_soil_id' => $supplier->id,
            'soil_consumable_id' => $soilConsumable->id,
        ]);

        // Test relationships
        $this->assertInstanceOf(SeedEntry::class, $recipe->seedEntry);
        $this->assertEquals($seedEntry->id, $recipe->seedEntry->id);

        $this->assertInstanceOf(Supplier::class, $recipe->soilSupplier);
        $this->assertEquals($supplier->id, $recipe->soilSupplier->id);

        $this->assertInstanceOf(Consumable::class, $recipe->soilConsumable);
        $this->assertEquals($soilConsumable->id, $recipe->soilConsumable->id);
    }

    /** @test */
    public function recipe_validates_required_fields(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempt to create recipe without required fields
        Recipe::create([
            'name' => null, // Should fail
        ]);
    }

    /** @test */
    public function recipe_casts_fields_correctly(): void
    {
        $recipe = Recipe::factory()->create([
            'germination_days' => '3.5',
            'blackout_days' => '2.0',
            'days_to_maturity' => '14.5',
            'light_days' => '9.0',
            'seed_soak_hours' => '12',
            'expected_yield_grams' => '200.50',
            'buffer_percentage' => '10.25',
            'seed_density_grams_per_tray' => '25.75',
            'is_active' => '1',
            'lot_depleted_at' => '2025-01-15 10:30:00',
        ]);

        // Test numeric casts
        $this->assertIsFloat($recipe->germination_days);
        $this->assertEquals(3.5, $recipe->germination_days);

        $this->assertIsFloat($recipe->blackout_days);
        $this->assertEquals(2.0, $recipe->blackout_days);

        $this->assertIsFloat($recipe->days_to_maturity);
        $this->assertEquals(14.5, $recipe->days_to_maturity);

        $this->assertIsInt($recipe->seed_soak_hours);
        $this->assertEquals(12, $recipe->seed_soak_hours);

        $this->assertIsFloat($recipe->expected_yield_grams);
        $this->assertEquals(200.5, $recipe->expected_yield_grams);

        // Test boolean cast
        $this->assertIsBool($recipe->is_active);
        $this->assertTrue($recipe->is_active);

        // Test datetime cast
        $this->assertInstanceOf(\Carbon\Carbon::class, $recipe->lot_depleted_at);
        $this->assertEquals('2025-01-15 10:30:00', $recipe->lot_depleted_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function recipe_fillable_fields_are_correct(): void
    {
        $recipe = new Recipe();
        $fillable = $recipe->getFillable();

        $expectedFields = [
            'name',
            'seed_entry_id',
            'common_name',
            'cultivar_name',
            'seed_consumable_id',
            'lot_number',
            'lot_depleted_at',
            'supplier_soil_id',
            'soil_consumable_id',
            'seed_density',
            'germination_days',
            'blackout_days',
            'days_to_maturity',
            'light_days',
            'harvest_days',
            'seed_soak_hours',
            'expected_yield_grams',
            'buffer_percentage',
            'seed_density_grams_per_tray',
            'is_active',
            'notes',
            'suspend_water_hours',
        ];

        foreach ($expectedFields as $field) {
            $this->assertContains($field, $fillable, "Field '{$field}' should be fillable");
        }
    }

    /** @test */
    public function recipe_handles_edge_cases_gracefully(): void
    {
        // Recipe without lot number
        $recipe = Recipe::factory()->create([
            'lot_number' => null,
        ]);

        $this->assertEquals(0, $recipe->lotConsumables()->count());
        $this->assertEquals(0, $recipe->availableLotConsumables()->count());
        $this->assertEquals(0.0, $recipe->getLotQuantity());
        $this->assertTrue($recipe->isLotDepleted());
        $this->assertFalse($recipe->canExecute(1.0));

        // Recipe with empty lot number
        $recipe2 = Recipe::factory()->create([
            'lot_number' => '',
        ]);

        $this->assertEquals(0, $recipe2->lotConsumables()->count());
        $this->assertEquals(0, $recipe2->availableLotConsumables()->count());

        // Recipe with non-existent lot
        $recipe3 = Recipe::factory()->create([
            'lot_number' => 'NON_EXISTENT_LOT_12345',
        ]);

        $this->assertEquals(0.0, $recipe3->getLotQuantity());
        $this->assertTrue($recipe3->isLotDepleted());
    }

    /** @test */
    public function recipe_filters_consumables_by_seed_type_only(): void
    {
        // Create mixed consumables with same lot number
        $seedConsumable = Consumable::factory()->create([
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id,
            'lot_no' => 'MIXED_LOT_TEST',
            'total_quantity' => 500.0,
            'consumed_quantity' => 0.0,
            'is_active' => true,
        ]);

        $soilConsumable = Consumable::factory()->create([
            'type' => 'soil',
            'lot_no' => 'MIXED_LOT_TEST', // Same lot number
            'total_quantity' => 300.0,
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'MIXED_LOT_TEST',
        ]);

        $lotConsumables = $recipe->lotConsumables();

        // Should only include seed consumables
        $this->assertCount(1, $lotConsumables);
        $this->assertTrue($lotConsumables->contains($seedConsumable));
        $this->assertFalse($lotConsumables->contains($soilConsumable));

        // Quantity should only count seed consumables
        $this->assertEquals(500.0, $recipe->getLotQuantity());
    }

    /** @test */
    public function recipe_respects_consumable_active_status(): void
    {
        // Create active and inactive consumables
        $activeConsumable = Consumable::factory()->create([
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id,
            'lot_no' => 'ACTIVE_TEST_LOT',
            'total_quantity' => 500.0,
            'consumed_quantity' => 0.0,
            'is_active' => true,
        ]);

        $inactiveConsumable = Consumable::factory()->create([
            'consumable_type_id' => \App\Models\ConsumableType::firstOrCreate(['code' => 'seed'], ['name' => 'Seed', 'description' => 'Seed type'])->id,
            'lot_no' => 'ACTIVE_TEST_LOT',
            'total_quantity' => 300.0,
            'consumed_quantity' => 0.0,
            'is_active' => false,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'ACTIVE_TEST_LOT',
        ]);

        // lotConsumables should include both (based on current implementation)
        $allLotConsumables = $recipe->lotConsumables();
        $this->assertCount(1, $allLotConsumables); // Only active ones are included
        $this->assertTrue($allLotConsumables->contains($activeConsumable));

        // availableLotConsumables should only include active ones
        $availableConsumables = $recipe->availableLotConsumables();
        $this->assertCount(1, $availableConsumables);
        $this->assertTrue($availableConsumables->contains($activeConsumable));
        $this->assertFalse($availableConsumables->contains($inactiveConsumable));

        // Quantity should only count active consumables
        $this->assertEquals(500.0, $recipe->getLotQuantity());
    }
}
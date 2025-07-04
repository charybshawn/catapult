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
use App\Models\Crop;
use App\Models\User;
use App\Models\ConsumableTransaction;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

class RecipeTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required lookup data
        ConsumableType::factory()->create([
            'code' => 'seed',
            'name' => 'Seeds',
            'is_active' => true,
        ]);
        
        ConsumableType::factory()->create([
            'code' => 'soil',
            'name' => 'Soil',
            'is_active' => true,
        ]);
        
        ConsumableUnit::factory()->create([
            'code' => 'unit',
            'name' => 'Unit',
            'symbol' => 'unit',
            'is_active' => true,
        ]);
        
        SupplierType::factory()->create([
            'code' => 'soil',
            'name' => 'Soil Supplier',
            'is_active' => true,
        ]);
        
        SupplierType::factory()->create([
            'code' => 'seed',
            'name' => 'Seed Supplier',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function recipe_tracks_complete_supply_chain_from_seed_to_harvest(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. Create supplier
        $seedSupplier = Supplier::factory()->create([
            'name' => 'Johnny\'s Seeds',
            'supplier_type_id' => SupplierType::where('code', 'seed')->first()->id,
        ]);

        $soilSupplier = Supplier::factory()->create([
            'name' => 'Premier Tech',
            'supplier_type_id' => SupplierType::where('code', 'soil')->first()->id,
        ]);

        // 2. Create seed entry (master catalog entry)
        $seedEntry = SeedEntry::factory()->create([
            'supplier_id' => $seedSupplier->id,
            'common_name' => 'Arugula',
            'cultivar_name' => 'Astro',
        ]);

        // 3. Create seed consumable with specific lot
        $seedConsumable = Consumable::factory()->create([
            'name' => 'Arugula (Astro)',
            'type' => 'seed',
            'supplier_id' => $seedSupplier->id,
            'seed_entry_id' => $seedEntry->id,
            'lot_no' => 'AST2025001',
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'is_active' => true,
        ]);

        // 4. Create soil consumable
        $soilConsumable = Consumable::factory()->create([
            'name' => 'Pro-Mix HP',
            'type' => 'soil',
            'supplier_id' => $soilSupplier->id,
            'initial_stock' => 20,
            'quantity_per_unit' => 107.0,
            'quantity_unit' => 'l',
            'is_active' => true,
        ]);

        // 5. Create recipe that links everything together
        $recipe = Recipe::factory()->create([
            'name' => 'Arugula (Astro) - 20G - 14 DTM - AST2025001',
            'common_name' => 'Arugula',
            'cultivar_name' => 'Astro',
            'seed_entry_id' => $seedEntry->id,
            'lot_number' => 'AST2025001',
            'soil_consumable_id' => $soilConsumable->id,
            'supplier_soil_id' => $soilSupplier->id,
            'seed_density_grams_per_tray' => 20.0,
            'days_to_maturity' => 14.0,
            'expected_yield_grams' => 180.0,
            'is_active' => true,
        ]);

        // 6. Verify complete traceability chain
        // Recipe -> Seed Entry -> Supplier
        $this->assertEquals($seedEntry->id, $recipe->seed_entry_id);
        $this->assertEquals($seedEntry->supplier_id, $seedSupplier->id);
        $this->assertEquals('Johnny\'s Seeds', $recipe->seedEntry->supplier->name);

        // Recipe -> Lot -> Consumable -> Supplier
        $this->assertEquals('AST2025001', $recipe->lot_number);
        $lotConsumables = $recipe->lotConsumables();
        $this->assertCount(1, $lotConsumables);
        $this->assertEquals($seedSupplier->id, $lotConsumables->first()->supplier_id);

        // Recipe -> Soil -> Supplier
        $this->assertEquals($soilConsumable->id, $recipe->soil_consumable_id);
        $this->assertEquals($soilSupplier->id, $recipe->soilConsumable->supplier_id);
        $this->assertEquals('Premier Tech', $recipe->soilConsumable->supplier->name);

        // Verify we can trace back to original seed variety
        $this->assertEquals('Arugula', $recipe->common_name);
        $this->assertEquals('Astro', $recipe->cultivar_name);
        $this->assertEquals('Arugula', $recipe->seedEntry->common_name);
        $this->assertEquals('Astro', $recipe->seedEntry->cultivar_name);
    }

    /** @test */
    public function recipe_tracks_inventory_consumption_through_crops(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create seed consumable
        $seedConsumable = Consumable::factory()->create([
            'name' => 'Kale (Red Russian)',
            'type' => 'seed',
            'lot_no' => 'KRR2025',
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'is_active' => true,
        ]);

        // Create recipe
        $recipe = Recipe::factory()->create([
            'name' => 'Kale (Red Russian) - 25G - 14 DTM - KRR2025',
            'lot_number' => 'KRR2025',
            'seed_density_grams_per_tray' => 25.0,
            'days_to_maturity' => 14.0,
            'is_active' => true,
        ]);

        // Initial state: no consumption
        $this->assertEquals(1000.0, $recipe->getLotQuantity());
        $this->assertFalse($recipe->isLotDepleted());
        $this->assertTrue($recipe->canExecute(25.0));

        // Create crops that will consume seed inventory
        $crop1 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'tray_count' => 10, // Will consume 250g (10 trays × 25g/tray)
            'current_stage' => 'germination',
        ]);

        // Simulate inventory consumption
        $inventoryService = app(InventoryService::class);
        $inventoryService->recordConsumption(
            $seedConsumable,
            250.0, // 10 trays × 25g/tray
            'g',
            $user,
            'Crop',
            $crop1->id,
            'Seeds used for crop planting'
        );

        // Verify consumption is tracked
        $seedConsumable->refresh();
        $this->assertEquals(250.0, $seedConsumable->consumed_quantity);
        $this->assertEquals(750.0, $recipe->getLotQuantity());

        // Create second crop
        $crop2 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'tray_count' => 20, // Will consume 500g more
            'current_stage' => 'germination',
        ]);

        // Consume more inventory
        $inventoryService->recordConsumption(
            $seedConsumable,
            500.0,
            'g',
            $user,
            'Crop',
            $crop2->id,
            'Seeds used for second crop planting'
        );

        // Verify total consumption
        $seedConsumable->refresh();
        $this->assertEquals(750.0, $seedConsumable->consumed_quantity);
        $this->assertEquals(250.0, $recipe->getLotQuantity());

        // Verify traceability: Recipe -> Crops -> Transactions
        $crops = $recipe->crops;
        $this->assertCount(2, $crops);

        $transactions = ConsumableTransaction::where('consumable_id', $seedConsumable->id)
            ->where('type', 'consumption')
            ->get();
        $this->assertCount(2, $transactions);

        // Verify we can trace from transaction back to crop and recipe
        $firstTransaction = $transactions->where('reference_id', $crop1->id)->first();
        $this->assertEquals('Crop', $firstTransaction->reference_type);
        $this->assertEquals($crop1->id, $firstTransaction->reference_id);
        $this->assertEquals(250.0, $firstTransaction->amount);

        $secondTransaction = $transactions->where('reference_id', $crop2->id)->first();
        $this->assertEquals('Crop', $secondTransaction->reference_type);
        $this->assertEquals($crop2->id, $secondTransaction->reference_id);
        $this->assertEquals(500.0, $secondTransaction->amount);
    }

    /** @test */
    public function recipe_tracks_lot_depletion_and_switches(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create initial lot
        $originalConsumable = Consumable::factory()->create([
            'name' => 'Basil (Genovese)',
            'type' => 'seed',
            'lot_no' => 'BG001',
            'total_quantity' => 100.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'is_active' => true,
        ]);

        // Create recipe using this lot
        $recipe = Recipe::factory()->create([
            'name' => 'Basil (Genovese) - 30G - 12 DTM - BG001',
            'lot_number' => 'BG001',
            'seed_density_grams_per_tray' => 30.0,
            'days_to_maturity' => 12.0,
            'is_active' => true,
        ]);

        // Verify initial state
        $this->assertEquals(100.0, $recipe->getLotQuantity());
        $this->assertFalse($recipe->isLotDepleted());
        $this->assertTrue($recipe->canExecute(30.0));

        // Consume most of the lot
        $inventoryService = app(InventoryService::class);
        $inventoryService->recordConsumption(
            $originalConsumable,
            95.0,
            'g',
            $user,
            'Test',
            null,
            'Nearly depleting lot'
        );

        // Verify near depletion
        $originalConsumable->refresh();
        $this->assertEquals(5.0, $recipe->getLotQuantity());
        $this->assertFalse($recipe->isLotDepleted());
        $this->assertFalse($recipe->canExecute(30.0)); // Can't make a full tray

        // Fully deplete the lot
        $inventoryService->recordConsumption(
            $originalConsumable,
            5.0,
            'g',
            $user,
            'Test',
            null,
            'Fully depleting lot'
        );

        // Verify depletion
        $originalConsumable->refresh();
        $this->assertEquals(0.0, $recipe->getLotQuantity());
        $this->assertTrue($recipe->isLotDepleted());
        $this->assertFalse($recipe->canExecute(1.0));

        // Mark recipe lot as depleted
        $recipe->markLotDepleted();
        $this->assertNotNull($recipe->lot_depleted_at);

        // Create new lot for the same variety
        $newConsumable = Consumable::factory()->create([
            'name' => 'Basil (Genovese)',
            'type' => 'seed',
            'lot_no' => 'BG002',
            'total_quantity' => 500.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'is_active' => true,
        ]);

        // Update recipe to use new lot
        $recipe->update([
            'lot_number' => 'BG002',
            'lot_depleted_at' => null,
            'name' => 'Basil (Genovese) - 30G - 12 DTM - BG002',
        ]);

        // Verify recipe can execute again
        $this->assertEquals(500.0, $recipe->getLotQuantity());
        $this->assertFalse($recipe->isLotDepleted());
        $this->assertTrue($recipe->canExecute(30.0));

        // Verify traceability shows lot switch
        $this->assertEquals('BG002', $recipe->lot_number);
        $newLotConsumables = $recipe->lotConsumables();
        $this->assertCount(1, $newLotConsumables);
        $this->assertEquals('BG002', $newLotConsumables->first()->lot_no);
    }

    /** @test */
    public function recipe_maintains_audit_trail_through_activity_log(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create recipe
        $recipe = Recipe::factory()->create([
            'name' => 'Test Recipe',
            'lot_number' => 'TEST001',
            'seed_density_grams_per_tray' => 20.0,
            'days_to_maturity' => 10.0,
            'is_active' => true,
        ]);

        // Verify creation was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Recipe::class,
            'subject_id' => $recipe->id,
            'event' => 'created',
        ]);

        // Update recipe
        $recipe->update([
            'seed_density_grams_per_tray' => 25.0,
            'days_to_maturity' => 12.0,
        ]);

        // Verify update was logged
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Recipe::class,
            'subject_id' => $recipe->id,
            'event' => 'updated',
        ]);

        // Get latest activity
        $latestActivity = Activity::where('subject_type', Recipe::class)
            ->where('subject_id', $recipe->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($latestActivity);
        $this->assertArrayHasKey('attributes', $latestActivity->properties);
        $this->assertArrayHasKey('old', $latestActivity->properties);

        // Verify old and new values are tracked
        $properties = $latestActivity->properties;
        $this->assertEquals(25.0, $properties['attributes']['seed_density_grams_per_tray']);
        $this->assertEquals(20.0, $properties['old']['seed_density_grams_per_tray']);
        $this->assertEquals(12.0, $properties['attributes']['days_to_maturity']);
        $this->assertEquals(10.0, $properties['old']['days_to_maturity']);
    }

    /** @test */
    public function recipe_provides_complete_ingredient_traceability(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create suppliers
        $seedSupplier = Supplier::factory()->create(['name' => 'Organic Seed Co.']);
        $soilSupplier = Supplier::factory()->create(['name' => 'Growing Medium Inc.']);

        // Create seed with full traceability
        $seedEntry = SeedEntry::factory()->create([
            'supplier_id' => $seedSupplier->id,
            'common_name' => 'Lettuce',
            'cultivar_name' => 'Buttercrunch',
            'supplier_product_title' => 'Organic Buttercrunch Lettuce Seeds',
        ]);

        $seedConsumable = Consumable::factory()->create([
            'name' => 'Lettuce (Buttercrunch)',
            'type' => 'seed',
            'supplier_id' => $seedSupplier->id,
            'seed_entry_id' => $seedEntry->id,
            'lot_no' => 'LBC2025',
            'total_quantity' => 800.0,
            'quantity_unit' => 'g',
            'is_active' => true,
        ]);

        // Create soil with traceability
        $soilConsumable = Consumable::factory()->create([
            'name' => 'Premium Potting Mix',
            'type' => 'soil',
            'supplier_id' => $soilSupplier->id,
            'initial_stock' => 15,
            'quantity_per_unit' => 50.0,
            'quantity_unit' => 'l',
            'is_active' => true,
        ]);

        // Create recipe linking all components
        $recipe = Recipe::factory()->create([
            'name' => 'Lettuce (Buttercrunch) - 15G - 28 DTM - LBC2025',
            'common_name' => 'Lettuce',
            'cultivar_name' => 'Buttercrunch',
            'seed_entry_id' => $seedEntry->id,
            'lot_number' => 'LBC2025',
            'soil_consumable_id' => $soilConsumable->id,
            'supplier_soil_id' => $soilSupplier->id,
            'seed_density_grams_per_tray' => 15.0,
            'days_to_maturity' => 28.0,
            'is_active' => true,
        ]);

        // Test complete ingredient traceability
        $traceabilityData = [
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'variety' => $recipe->common_name . ' (' . $recipe->cultivar_name . ')',
                'created_at' => $recipe->created_at,
            ],
            'seed' => [
                'lot_number' => $recipe->lot_number,
                'supplier' => $recipe->seedEntry->supplier->name,
                'product_title' => $recipe->seedEntry->supplier_product_title,
                'variety' => $recipe->seedEntry->common_name . ' (' . $recipe->seedEntry->cultivar_name . ')',
                'available_quantity' => $recipe->getLotQuantity(),
            ],
            'soil' => [
                'name' => $recipe->soilConsumable->name,
                'supplier' => $recipe->soilConsumable->supplier->name,
                'available_units' => $recipe->soilConsumable->current_stock,
            ],
        ];

        // Verify all traceability data is accessible
        $this->assertEquals('Lettuce (Buttercrunch) - 15G - 28 DTM - LBC2025', $traceabilityData['recipe']['name']);
        $this->assertEquals('Lettuce (Buttercrunch)', $traceabilityData['recipe']['variety']);
        
        $this->assertEquals('LBC2025', $traceabilityData['seed']['lot_number']);
        $this->assertEquals('Organic Seed Co.', $traceabilityData['seed']['supplier']);
        $this->assertEquals('Organic Buttercrunch Lettuce Seeds', $traceabilityData['seed']['product_title']);
        $this->assertEquals(800.0, $traceabilityData['seed']['available_quantity']);
        
        $this->assertEquals('Premium Potting Mix', $traceabilityData['soil']['name']);
        $this->assertEquals('Growing Medium Inc.', $traceabilityData['soil']['supplier']);
    }

    /** @test */
    public function recipe_tracks_batch_and_harvest_traceability(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create seed consumable
        $seedConsumable = Consumable::factory()->create([
            'name' => 'Spinach (Space)',
            'type' => 'seed',
            'lot_no' => 'SP2025',
            'total_quantity' => 600.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'is_active' => true,
        ]);

        // Create recipe
        $recipe = Recipe::factory()->create([
            'name' => 'Spinach (Space) - 20G - 21 DTM - SP2025',
            'lot_number' => 'SP2025',
            'seed_density_grams_per_tray' => 20.0,
            'days_to_maturity' => 21.0,
            'expected_yield_grams' => 150.0,
            'is_active' => true,
        ]);

        // Create multiple crops (batches) using this recipe
        $crop1 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'tray_count' => 5,
            'planted_at' => now()->subDays(21),
            'current_stage' => 'harvested',
            'notes' => 'Batch 1 - Early harvest',
        ]);

        $crop2 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'tray_count' => 8,
            'planted_at' => now()->subDays(18),
            'current_stage' => 'light',
            'notes' => 'Batch 2 - Main production',
        ]);

        $crop3 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'tray_count' => 3,
            'planted_at' => now()->subDays(15),
            'current_stage' => 'germination',
            'notes' => 'Batch 3 - Late planting',
        ]);

        // Verify batch traceability
        $crops = $recipe->crops()->orderBy('planted_at')->get();
        $this->assertCount(3, $crops);

        // Test we can trace from recipe to all batches
        foreach ($crops as $index => $crop) {
            $this->assertEquals($recipe->id, $crop->recipe_id);
            $this->assertEquals('SP2025', $crop->recipe->lot_number);
            $this->assertEquals('Spinach (Space)', $crop->recipe->common_name);
            $this->assertStringContains("Batch " . ($index + 1), $crop->notes);
        }

        // Test we can calculate total seed consumption across all batches
        $totalTrays = $crops->sum('tray_count'); // 5 + 8 + 3 = 16
        $expectedSeedConsumption = $totalTrays * $recipe->seed_density_grams_per_tray; // 16 × 20 = 320g
        
        $this->assertEquals(16, $totalTrays);
        $this->assertEquals(320.0, $expectedSeedConsumption);

        // Test expected yield calculation
        $expectedTotalYield = $totalTrays * $recipe->expected_yield_grams; // 16 × 150 = 2400g
        $this->assertEquals(2400.0, $expectedTotalYield);

        // Verify we can still trace back to original seed lot
        foreach ($crops as $crop) {
            $this->assertEquals('SP2025', $crop->recipe->lot_number);
            $lotConsumables = $crop->recipe->lotConsumables();
            $this->assertCount(1, $lotConsumables);
            $this->assertEquals('SP2025', $lotConsumables->first()->lot_no);
        }
    }
}
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Services\LotInventoryService;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use App\Models\Recipe;
use App\Models\User;
use App\Models\ConsumableTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FifoInventoryWorkflowTest extends TestCase
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
        
        ConsumableUnit::factory()->create([
            'code' => 'gram',
            'name' => 'Gram',
            'symbol' => 'g',
            'is_active' => true,
        ]);
    }

    public function test_complete_fifo_workflow_with_multiple_lot_entries(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $inventoryService = app(InventoryService::class);
        $lotInventoryService = app(LotInventoryService::class);

        // Step 1: Create multiple consumable entries for the same lot (simulating multiple deliveries)
        $firstEntry = Consumable::factory()->create([
            'name' => 'Arugula Seeds',
            'type' => 'seed',
            'lot_no' => 'ARG2025001',
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0,
            'created_at' => now()->subDays(20), // Oldest entry
            'is_active' => true,
        ]);

        $secondEntry = Consumable::factory()->create([
            'name' => 'Arugula Seeds',
            'type' => 'seed',
            'lot_no' => 'ARG2025001',
            'total_quantity' => 500.0,
            'consumed_quantity' => 0,
            'created_at' => now()->subDays(10), // Newer entry
            'is_active' => true,
        ]);

        $thirdEntry = Consumable::factory()->create([
            'name' => 'Arugula Seeds',
            'type' => 'seed',
            'lot_no' => 'ARG2025001',
            'total_quantity' => 800.0,
            'consumed_quantity' => 0,
            'created_at' => now()->subDays(5), // Newest entry
            'is_active' => true,
        ]);

        // Step 2: Create a recipe that uses this lot
        $recipe = Recipe::factory()->create([
            'name' => 'Arugula Microgreen Recipe',
            'lot_number' => 'ARG2025001',
            'seed_density' => 10,
            'seed_density_grams_per_tray' => 10.0,
            'days_to_maturity' => 12,
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 7,
            'harvest_days' => 12,
            'is_active' => true,
        ]);

        // Step 3: Initialize transaction tracking for all entries
        $inventoryService->initializeTransactionTracking($firstEntry);
        $inventoryService->initializeTransactionTracking($secondEntry);
        $inventoryService->initializeTransactionTracking($thirdEntry);

        // Step 4: Verify initial lot status
        $initialQuantity = $lotInventoryService->getLotQuantity('ARG2025001');
        $this->assertEquals(2300.0, $initialQuantity); // 1000 + 500 + 800

        $this->assertFalse($lotInventoryService->isLotDepleted('ARG2025001'));
        $this->assertTrue($inventoryService->canConsumeFromLot('ARG2025001', 1200.0));

        // Step 5: First consumption - should consume from oldest entry first
        $firstConsumption = $inventoryService->consumeFromLot(
            'ARG2025001',
            800.0, // Less than first entry, should only affect first entry
            $recipe,
            $user
        );

        $this->assertCount(1, $firstConsumption);
        $this->assertEquals($firstEntry->id, $firstConsumption[0]['consumable_id']);
        $this->assertEquals(800.0, $firstConsumption[0]['amount']);
        $this->assertEquals(200.0, floatval($firstConsumption[0]['remaining_after'])); // 1000 - 800

        // Verify lot quantity after first consumption
        $quantityAfterFirst = $lotInventoryService->getLotQuantity('ARG2025001');
        $this->assertEquals(1500.0, $quantityAfterFirst); // 2300 - 800

        // Step 6: Second consumption - should finish first entry and start second
        $secondConsumption = $inventoryService->consumeFromLot(
            'ARG2025001',
            600.0, // Will consume remaining 200 from first + 400 from second
            $recipe,
            $user
        );

        $this->assertCount(2, $secondConsumption);
        
        // First entry should be fully depleted
        $this->assertEquals($firstEntry->id, $secondConsumption[0]['consumable_id']);
        $this->assertEquals(200.0, $secondConsumption[0]['amount']); // Remaining from first entry
        $this->assertEquals(0.0, floatval($secondConsumption[0]['remaining_after']));

        // Second entry should be partially consumed
        $this->assertEquals($secondEntry->id, $secondConsumption[1]['consumable_id']);
        $this->assertEquals(400.0, $secondConsumption[1]['amount']);
        $this->assertEquals(100.0, floatval($secondConsumption[1]['remaining_after'])); // 500 - 400

        // Verify lot quantity after second consumption
        $quantityAfterSecond = $lotInventoryService->getLotQuantity('ARG2025001');
        $this->assertEquals(900.0, $quantityAfterSecond); // 1500 - 600

        // Step 7: Large consumption that spans multiple entries
        $thirdConsumption = $inventoryService->consumeFromLot(
            'ARG2025001',
            850.0, // Will consume remaining 100 from second + 750 from third
            $recipe,
            $user
        );

        $this->assertCount(2, $thirdConsumption);

        // Second entry should be fully depleted
        $this->assertEquals($secondEntry->id, $thirdConsumption[0]['consumable_id']);
        $this->assertEquals(100.0, $thirdConsumption[0]['amount']);
        $this->assertEquals(0.0, floatval($thirdConsumption[0]['remaining_after']));

        // Third entry should be partially consumed
        $this->assertEquals($thirdEntry->id, $thirdConsumption[1]['consumable_id']);
        $this->assertEquals(750.0, $thirdConsumption[1]['amount']);
        $this->assertEquals(50.0, floatval($thirdConsumption[1]['remaining_after'])); // 800 - 750

        // Step 8: Verify final lot status
        $finalQuantity = $lotInventoryService->getLotQuantity('ARG2025001');
        $this->assertEquals(50.0, $finalQuantity); // Only 50g remaining in third entry

        $this->assertFalse($lotInventoryService->isLotDepleted('ARG2025001')); // Still has 50g
        $this->assertTrue($inventoryService->canConsumeFromLot('ARG2025001', 50.0));
        $this->assertFalse($inventoryService->canConsumeFromLot('ARG2025001', 51.0));

        // Step 9: Verify transaction audit trail
        $allTransactions = ConsumableTransaction::where('type', 'consumption')
            ->whereIn('consumable_id', [$firstEntry->id, $secondEntry->id, $thirdEntry->id])
            ->orderBy('created_at')
            ->get();

        $this->assertCount(5, $allTransactions); // 1 + 2 + 2 transactions

        // Verify metadata contains FIFO information
        foreach ($allTransactions as $transaction) {
            $this->assertArrayHasKey('lot_number', $transaction->metadata);
            $this->assertEquals('ARG2025001', $transaction->metadata['lot_number']);
            $this->assertArrayHasKey('fifo_consumption', $transaction->metadata);
            $this->assertTrue($transaction->metadata['fifo_consumption']);
            $this->assertArrayHasKey('recipe_id', $transaction->metadata);
            $this->assertEquals($recipe->id, $transaction->metadata['recipe_id']);
        }

        // Step 10: Final consumption to deplete the lot
        $finalConsumption = $inventoryService->consumeFromLot(
            'ARG2025001',
            50.0,
            $recipe,
            $user
        );

        $this->assertCount(1, $finalConsumption);
        $this->assertEquals($thirdEntry->id, $finalConsumption[0]['consumable_id']);
        $this->assertEquals(0.0, floatval($finalConsumption[0]['remaining_after']));

        // Lot should now be depleted
        $this->assertEquals(0.0, $lotInventoryService->getLotQuantity('ARG2025001'));
        $this->assertTrue($lotInventoryService->isLotDepleted('ARG2025001'));
        $this->assertFalse($inventoryService->canConsumeFromLot('ARG2025001', 1.0));
    }

    public function test_fifo_consumption_respects_lot_boundaries(): void
    {
        $user = User::factory()->create();
        $inventoryService = app(InventoryService::class);

        // Create consumables for different lots
        $lot1Entry = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'LOT001',
            'total_quantity' => 500.0,
            'created_at' => now()->subDays(10),
            'is_active' => true,
        ]);

        $lot2Entry = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'LOT002',
            'total_quantity' => 500.0,
            'created_at' => now()->subDays(5), // Newer, but different lot
            'is_active' => true,
        ]);

        $inventoryService->initializeTransactionTracking($lot1Entry);
        $inventoryService->initializeTransactionTracking($lot2Entry);

        // Consumption from LOT001 should not affect LOT002
        $consumption = $inventoryService->consumeFromLot('LOT001', 300.0, null, $user);

        $this->assertCount(1, $consumption);
        $this->assertEquals($lot1Entry->id, $consumption[0]['consumable_id']);

        // Verify LOT002 remains untouched
        $lot2Remaining = $inventoryService->getCurrentStockFromTransactions($lot2Entry);
        $this->assertEquals(500.0, $lot2Remaining);
    }

    public function test_fifo_consumption_handles_insufficient_stock_gracefully(): void
    {
        $user = User::factory()->create();
        $inventoryService = app(InventoryService::class);

        $consumable = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'SMALL_LOT',
            'total_quantity' => 100.0,
            'is_active' => true,
        ]);

        $inventoryService->initializeTransactionTracking($consumable);

        // Attempt to consume more than available
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock in lot');

        $inventoryService->consumeFromLot('SMALL_LOT', 150.0, null, $user);
    }

    public function test_fifo_consumption_with_recipe_integration(): void
    {
        $user = User::factory()->create();
        $inventoryService = app(InventoryService::class);

        $consumable = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'RECIPE_LOT',
            'total_quantity' => 1000.0,
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'name' => 'Test Recipe',
            'lot_number' => 'RECIPE_LOT',
            'seed_density' => 10,
            'is_active' => true,
        ]);

        $inventoryService->initializeTransactionTracking($consumable);

        // Test Recipe model integration
        $this->assertEquals(1000.0, $recipe->getLotQuantity());
        $this->assertFalse($recipe->isLotDepleted());
        $this->assertTrue($recipe->canExecute(500.0));

        // Consume through inventory service
        $consumption = $inventoryService->consumeFromLot('RECIPE_LOT', 600.0, $recipe, $user);

        // Verify recipe methods reflect the consumption
        $this->assertEquals(400.0, $recipe->getLotQuantity());
        $this->assertFalse($recipe->isLotDepleted());
        $this->assertTrue($recipe->canExecute(300.0));
        $this->assertFalse($recipe->canExecute(500.0));

        // Verify transaction metadata includes recipe information
        $transaction = ConsumableTransaction::where('consumable_id', $consumable->id)
            ->where('type', 'consumption')
            ->first();

        $this->assertEquals($recipe->id, $transaction->metadata['recipe_id']);
        $this->assertEquals($recipe->name, $transaction->metadata['recipe_name']);
    }

    public function test_can_consume_from_lot_validation(): void
    {
        $inventoryService = app(InventoryService::class);

        // Create test consumables
        $consumable1 = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'TEST_LOT',
            'total_quantity' => 300.0,
            'consumed_quantity' => 100.0, // 200 available
            'is_active' => true,
        ]);

        $consumable2 = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'TEST_LOT',
            'total_quantity' => 500.0,
            'consumed_quantity' => 200.0, // 300 available
            'is_active' => true,
        ]);

        // Total available: 200 + 300 = 500
        $this->assertTrue($inventoryService->canConsumeFromLot('TEST_LOT', 500.0));
        $this->assertTrue($inventoryService->canConsumeFromLot('TEST_LOT', 250.0));
        $this->assertFalse($inventoryService->canConsumeFromLot('TEST_LOT', 501.0));

        // Non-existent lot
        $this->assertFalse($inventoryService->canConsumeFromLot('NON_EXISTENT', 1.0));
    }

    public function test_get_lot_consumption_plan(): void
    {
        $inventoryService = app(InventoryService::class);

        // Create entries with different ages
        $older = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'PLAN_LOT',
            'total_quantity' => 200.0,
            'consumed_quantity' => 50.0, // 150 available
            'created_at' => now()->subDays(15),
            'is_active' => true,
        ]);

        $newer = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'PLAN_LOT',
            'total_quantity' => 300.0,
            'consumed_quantity' => 0, // 300 available
            'created_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        // Plan consumption of 250g (should use 150 from older + 100 from newer)
        $plan = $inventoryService->getLotConsumptionPlan('PLAN_LOT', 250.0);

        $this->assertCount(2, $plan);
        
        // First entry should be the older one
        $this->assertEquals($older->id, $plan[0]['consumable_id']);
        $this->assertEquals(150.0, $plan[0]['amount_to_consume']);
        $this->assertEquals(0.0, $plan[0]['remaining_after']);

        // Second entry should be the newer one
        $this->assertEquals($newer->id, $plan[1]['consumable_id']);
        $this->assertEquals(100.0, $plan[1]['amount_to_consume']);
        $this->assertEquals(200.0, $plan[1]['remaining_after']);
    }
}
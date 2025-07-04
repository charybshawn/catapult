<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use App\Models\ConsumableTransaction;
use App\Models\User;
use App\Services\InventoryService;

class ConsumableTransactionIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required data
        ConsumableType::firstOrCreate(
            ['code' => 'seed'],
            [
                'name' => 'Seeds',
                'description' => 'Seed consumables for testing',
                'color' => 'green',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        // Create a specific unit for tests - using a fixed name to avoid conflicts
        ConsumableUnit::firstOrCreate(
            ['code' => 'test-unit'],
            [
                'name' => 'Test Unit',
                'symbol' => 'unit',
                'category' => 'count',
                'conversion_factor' => 1.0,
                'base_unit' => true,
                'is_active' => true,
            ]
        );
    }

    public function test_complete_consumable_lifecycle_with_transactions()
    {
        // Create a user for tracking
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create a consumable with initial stock
        $consumable = Consumable::factory()->create([
            'name' => 'Basil Seeds - Genovese',
            'total_quantity' => 1000.0,
            'quantity_unit' => 'g',
            'consumed_quantity' => 0,
            'restock_threshold' => 100.0,
        ]);

        $inventoryService = app(InventoryService::class);

        // Step 1: Initialize transaction tracking
        $initialTransaction = $inventoryService->initializeTransactionTracking($consumable);
        
        $this->assertNotNull($initialTransaction);
        $this->assertEquals('initial', $initialTransaction->type);
        $this->assertEquals(1000.0, $initialTransaction->quantity);
        $this->assertEquals(1000.0, $initialTransaction->balance_after);

        // Step 2: Record consumption for crop seeding
        $consumptionTransaction = $consumable->recordConsumption(
            amount: 150.0,
            unit: 'g',
            user: $user,
            referenceType: 'crop',
            referenceId: 1,
            notes: 'Used for basil crop seeding - Batch A',
            metadata: ['crop_name' => 'Basil Batch A', 'tray_count' => 20]
        );

        $this->assertEquals('consumption', $consumptionTransaction->type);
        $this->assertEquals(-150.0, $consumptionTransaction->quantity);
        $this->assertEquals(850.0, $consumptionTransaction->balance_after);
        $this->assertEquals($user->id, $consumptionTransaction->user_id);

        // Step 3: Add new stock from supplier
        $additionTransaction = $consumable->recordAddition(
            amount: 500.0,
            unit: 'g',
            user: $user,
            referenceType: 'order',
            referenceId: 42,
            notes: 'New shipment from Johnny Seeds',
            metadata: ['supplier_lot' => 'JS2025-001', 'expiry_date' => '2026-12-31']
        );

        $this->assertEquals('addition', $additionTransaction->type);
        $this->assertEquals(500.0, $additionTransaction->quantity);
        $this->assertEquals(1350.0, $additionTransaction->balance_after);

        // Step 4: Record waste/damage
        $wasteTransaction = ConsumableTransaction::create([
            'consumable_id' => $consumable->id,
            'type' => ConsumableTransaction::TYPE_WASTE,
            'quantity' => -25.0,
            'balance_after' => 1325.0,
            'user_id' => $user->id,
            'notes' => 'Damaged package during handling',
            'metadata' => ['damage_type' => 'water_damage'],
        ]);

        // Step 5: Verify transaction history
        $history = $inventoryService->getTransactionHistory($consumable);
        $this->assertCount(4, $history);

        // Verify chronological order (latest first)
        $this->assertEquals('waste', $history[0]->type);
        $this->assertEquals('addition', $history[1]->type);
        $this->assertEquals('consumption', $history[2]->type);
        $this->assertEquals('initial', $history[3]->type);

        // Step 6: Verify current stock calculation
        $currentStock = $inventoryService->getCurrentStockFromTransactions($consumable);
        $this->assertEquals(1325.0, $currentStock);

        // Step 7: Verify legacy fields are updated
        $consumable->refresh();
        $this->assertEquals(175.0, $consumable->consumed_quantity); // 150 + 25 waste
        $this->assertEquals(1325.0, $consumable->total_quantity);

        // Step 8: Test that consumable model methods work correctly
        $this->assertTrue($consumable->isUsingTransactionTracking());
        $this->assertEquals(1325.0, $consumable->getCurrentStockWithTransactions());

        // Step 9: Verify audit trail completeness
        $allTransactions = $consumable->consumableTransactions()->orderBy('created_at')->get();
        
        foreach ($allTransactions as $transaction) {
            $this->assertNotNull($transaction->created_at);
            $this->assertNotNull($transaction->balance_after);
            
            if ($transaction->type !== 'initial') {
                $this->assertNotNull($transaction->user_id);
            }
        }
    }

    public function test_consumable_without_transaction_tracking_fallback()
    {
        // Create a consumable without initializing transaction tracking
        $consumable = Consumable::factory()->create([
            'total_quantity' => 800.0,
            'consumed_quantity' => 200.0,
        ]);

        $inventoryService = app(InventoryService::class);

        // Should fall back to legacy calculation
        $currentStock = $inventoryService->getCurrentStockFromTransactions($consumable);
        $this->assertEquals(800.0, $currentStock); // For seeds, total_quantity is used directly

        $this->assertFalse($inventoryService->isUsingTransactionTracking($consumable));
        $this->assertFalse($consumable->isUsingTransactionTracking());
    }

    public function test_transaction_tracking_with_unit_conversions()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $consumable = Consumable::factory()->create([
            'total_quantity' => 2000.0, // 2kg in grams
            'quantity_unit' => 'g',
        ]);

        $inventoryService = app(InventoryService::class);
        $inventoryService->initializeTransactionTracking($consumable);

        // Record consumption in different units
        $consumable->recordConsumption(0.5, 'kg', $user, notes: 'Used 0.5kg for large crop');
        $consumable->recordConsumption(250.0, 'g', $user, notes: 'Used 250g for test batch');

        $currentStock = $inventoryService->getCurrentStockFromTransactions($consumable);
        
        // 2000g - 500g - 250g = 1250g
        $this->assertEquals(1250.0, $currentStock);

        // Verify both transactions were recorded correctly
        $transactions = $consumable->consumableTransactions()
            ->where('type', 'consumption')
            ->orderBy('created_at')
            ->get();

        $this->assertEquals(-500.0, $transactions[0]->quantity); // 0.5kg converted to grams
        $this->assertEquals(-250.0, $transactions[1]->quantity); // 250g as-is
    }

    public function test_concurrent_transaction_integrity()
    {
        $user = User::factory()->create();
        $consumable = Consumable::factory()->create(['total_quantity' => 1000.0]);
        
        $inventoryService = app(InventoryService::class);
        $inventoryService->initializeTransactionTracking($consumable);

        // Simulate concurrent transactions (in reality these would be separate requests)
        $transaction1 = $inventoryService->recordConsumption($consumable, 100.0, user: $user);
        $transaction2 = $inventoryService->recordConsumption($consumable, 200.0, user: $user);
        $transaction3 = $inventoryService->recordAddition($consumable, 50.0, user: $user);

        // Verify final balance is correct
        $finalBalance = $inventoryService->getCurrentStockFromTransactions($consumable);
        $this->assertEquals(750.0, $finalBalance); // 1000 - 100 - 200 + 50

        // Verify each transaction has correct balance_after
        $this->assertEquals(900.0, $transaction1->balance_after);
        $this->assertEquals(700.0, $transaction2->balance_after);
        $this->assertEquals(750.0, $transaction3->balance_after);
    }

    public function test_data_migration_creates_correct_transactions()
    {
        // Create consumables with existing data (simulating legacy system)
        $consumable1 = Consumable::factory()->create([
            'name' => 'Legacy Seed 1',
            'total_quantity' => 750.0,
            'consumed_quantity' => 250.0,
        ]);

        $consumable2 = Consumable::factory()->create([
            'name' => 'Legacy Seed 2',
            'total_quantity' => 0.0, // Empty stock
            'consumed_quantity' => 500.0,
        ]);

        // Run the migration logic (simulating the data migration)
        $inventoryService = app(InventoryService::class);
        
        // Only consumable1 should get transactions (has stock)
        $transaction1 = $inventoryService->initializeTransactionTracking($consumable1);
        $transaction2 = $inventoryService->initializeTransactionTracking($consumable2);

        $this->assertNotNull($transaction1);
        $this->assertNull($transaction2); // No stock, no transaction

        // Verify the transaction contains migration metadata
        $this->assertStringContainsString('Initial stock from legacy system', $transaction1->notes);
        $this->assertArrayHasKey('total_quantity', $transaction1->metadata);
        $this->assertArrayHasKey('consumed_quantity', $transaction1->metadata);
        $this->assertArrayHasKey('migrated_at', $transaction1->metadata);
    }
}

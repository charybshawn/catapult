<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\InventoryService;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableTransaction;
use App\Models\User;

class InventoryServiceTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = app(InventoryService::class);
        
        // Create required consumable type
        ConsumableType::factory()->create([
            'code' => 'seed',
            'name' => 'Seeds',
            'is_active' => true,
        ]);
    }

    public function test_record_consumption_creates_transaction()
    {
        $consumable = Consumable::factory()->create([
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0,
            'quantity_unit' => 'g', // Set unit to grams
        ]);
        $user = User::factory()->create();

        // Initialize transaction tracking first
        $this->inventoryService->initializeTransactionTracking($consumable);

        $transaction = $this->inventoryService->recordConsumption(
            $consumable,
            50.5,
            'g',
            $user,
            'crop',
            123,
            'Used for basil seeding'
        );

        $this->assertInstanceOf(ConsumableTransaction::class, $transaction);
        $this->assertEquals('consumption', $transaction->type);
        $this->assertEquals(-50.5, $transaction->quantity);
        $this->assertEquals(949.5, $transaction->balance_after);
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals('crop', $transaction->reference_type);
        $this->assertEquals(123, $transaction->reference_id);
    }

    public function test_record_addition_creates_transaction()
    {
        $consumable = Consumable::factory()->create([
            'total_quantity' => 500.0,
            'consumed_quantity' => 0,
            'quantity_unit' => 'g', // Set unit to grams to match test expectations
        ]);
        $user = User::factory()->create();

        // Initialize transaction tracking first
        $this->inventoryService->initializeTransactionTracking($consumable);

        $transaction = $this->inventoryService->recordAddition(
            $consumable,
            1000.0,
            'g',
            $user,
            'order',
            456,
            'New shipment arrived'
        );

        $this->assertInstanceOf(ConsumableTransaction::class, $transaction);
        $this->assertEquals('addition', $transaction->type);
        $this->assertEquals(1000.0, $transaction->quantity);
        $this->assertEquals(1500.0, $transaction->balance_after);
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals('order', $transaction->reference_type);
        $this->assertEquals(456, $transaction->reference_id);
    }

    public function test_get_current_stock_from_transactions()
    {
        $consumable = Consumable::factory()->create();

        // Create some transactions
        ConsumableTransaction::factory()->create([
            'consumable_id' => $consumable->id,
            'type' => 'initial',
            'quantity' => 1000.0,
            'balance_after' => 1000.0,
        ]);

        ConsumableTransaction::factory()->create([
            'consumable_id' => $consumable->id,
            'type' => 'consumption',
            'quantity' => -150.0,
            'balance_after' => 850.0,
        ]);

        $stock = $this->inventoryService->getCurrentStockFromTransactions($consumable);
        $this->assertEquals(850.0, $stock);
    }

    public function test_initialize_transaction_tracking()
    {
        $consumable = Consumable::factory()->create([
            'total_quantity' => 750.0,
            'consumed_quantity' => 250.0,
        ]);

        $transaction = $this->inventoryService->initializeTransactionTracking($consumable);

        $this->assertInstanceOf(ConsumableTransaction::class, $transaction);
        $this->assertEquals('initial', $transaction->type);
        $this->assertEquals(500.0, $transaction->quantity); // 750 - 250 = 500 current stock
        $this->assertEquals(500.0, $transaction->balance_after);
        $this->assertStringContainsString('Initial stock from legacy system', $transaction->notes);
    }

    public function test_initialize_tracking_does_not_duplicate()
    {
        $consumable = Consumable::factory()->create();

        // First initialization
        $transaction1 = $this->inventoryService->initializeTransactionTracking($consumable);
        $this->assertNotNull($transaction1);

        // Second attempt should return null
        $transaction2 = $this->inventoryService->initializeTransactionTracking($consumable);
        $this->assertNull($transaction2);

        // Should only have one transaction
        $this->assertEquals(1, $consumable->consumableTransactions()->count());
    }

    public function test_get_transaction_history()
    {
        $consumable = Consumable::factory()->create();
        $user = User::factory()->create();

        // Create multiple transactions
        ConsumableTransaction::factory()->count(5)->create([
            'consumable_id' => $consumable->id,
            'user_id' => $user->id,
        ]);

        $history = $this->inventoryService->getTransactionHistory($consumable, 3);

        $this->assertCount(3, $history);
        $this->assertEquals($consumable->id, $history->first()->consumable_id);
    }

    public function test_is_using_transaction_tracking()
    {
        $consumable = Consumable::factory()->create();

        // Initially not using transaction tracking
        $this->assertFalse($this->inventoryService->isUsingTransactionTracking($consumable));

        // After creating a transaction
        ConsumableTransaction::factory()->create([
            'consumable_id' => $consumable->id,
        ]);

        $this->assertTrue($this->inventoryService->isUsingTransactionTracking($consumable));
    }

    public function test_record_consumption_updates_legacy_fields()
    {
        $consumable = Consumable::factory()->create([
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0,
        ]);

        // Initialize and record consumption
        $this->inventoryService->initializeTransactionTracking($consumable);
        $this->inventoryService->recordConsumption($consumable, 100.0);

        // Check that legacy fields are updated
        $consumable->refresh();
        $this->assertEquals(100.0, $consumable->consumed_quantity);
    }

    public function test_record_addition_updates_legacy_fields()
    {
        $consumable = Consumable::factory()->create([
            'total_quantity' => 500.0,
            'consumed_quantity' => 0,
        ]);

        // Initialize and record addition
        $this->inventoryService->initializeTransactionTracking($consumable);
        $this->inventoryService->recordAddition($consumable, 200.0);

        // Check that legacy fields are updated for seeds
        $consumable->refresh();
        $this->assertEquals(700.0, $consumable->total_quantity);
    }

    public function test_unit_conversion_in_transactions()
    {
        $consumable = Consumable::factory()->create([
            'total_quantity' => 1000.0, // 1000g
            'consumed_quantity' => 0, // Start with no consumed
            'quantity_unit' => 'g',
        ]);

        // Initialize transaction tracking
        $this->inventoryService->initializeTransactionTracking($consumable);

        // Record consumption in kilograms (should convert to grams)
        $transaction = $this->inventoryService->recordConsumption(
            $consumable,
            0.1, // 0.1 kg = 100g
            'kg'
        );

        // Should be converted to grams
        $this->assertEquals(-100.0, $transaction->quantity);
        $this->assertEquals(900.0, $transaction->balance_after);
    }

    public function test_multiple_transactions_maintain_balance_integrity()
    {
        $consumable = Consumable::factory()->create([
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0, // Start with no consumed
            'quantity_unit' => 'g', // Set unit to grams
        ]);

        // Initialize tracking
        $this->inventoryService->initializeTransactionTracking($consumable);

        // Record multiple transactions
        $this->inventoryService->recordConsumption($consumable, 100.0);
        $this->inventoryService->recordAddition($consumable, 500.0);
        $this->inventoryService->recordConsumption($consumable, 200.0);

        $finalBalance = $this->inventoryService->getCurrentStockFromTransactions($consumable);
        
        // 1000 - 100 + 500 - 200 = 1200
        $this->assertEquals(1200.0, $finalBalance);
    }
}

<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Models\Consumable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    public function test_getCurrentStock_for_seed_consumables(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'seed',
            'total_quantity' => 100.0,
            'initial_stock' => 50,
            'consumed_quantity' => 10,
        ]);

        // With transaction system, it should use getCurrentStockFromTransactions
        $result = $this->inventoryService->getCurrentStock($consumable);

        // Should return total_quantity for seeds when no transactions exist
        $this->assertEquals(100.0, $result);
    }

    public function test_getCurrentStock_for_other_consumables(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 50.0,
            'consumed_quantity' => 10,
        ]);

        $result = $this->inventoryService->getCurrentStock($consumable);

        $this->assertEquals(40.0, $result);
    }

    public function test_getCurrentStock_never_goes_below_zero(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 10.0,
            'consumed_quantity' => 20,
        ]);

        $result = $this->inventoryService->getCurrentStock($consumable);

        $this->assertEquals(0.0, $result);
    }

    public function test_needsRestock_when_below_threshold(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 100.0,
            'consumed_quantity' => 90,
            'restock_threshold' => 20,
        ]);

        $result = $this->inventoryService->needsRestock($consumable);

        $this->assertTrue($result);
    }

    public function test_needsRestock_when_above_threshold(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 100.0,
            'consumed_quantity' => 50,
            'restock_threshold' => 20,
        ]);

        $result = $this->inventoryService->needsRestock($consumable);

        $this->assertFalse($result);
    }

    public function test_isOutOfStock_when_consumed_exceeds_initial(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 10.0,
            'consumed_quantity' => 15,
        ]);

        $result = $this->inventoryService->isOutOfStock($consumable);

        $this->assertTrue($result);
    }

    public function test_isOutOfStock_when_stock_available(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 50.0,
            'consumed_quantity' => 10,
        ]);

        $result = $this->inventoryService->isOutOfStock($consumable);

        $this->assertFalse($result);
    }

    public function test_deductStock_updates_consumed_quantity(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 100.0,
            'consumed_quantity' => 10,
        ]);

        $this->inventoryService->deductStock($consumable, 5.0);

        $this->assertEquals(15.0, $consumable->consumed_quantity);
    }

    public function test_addStock_updates_total_quantity(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 100.0,
            'consumed_quantity' => 10,
        ]);

        $this->inventoryService->addStock($consumable, 20.0);

        $this->assertEquals(120.0, $consumable->total_quantity);
    }

    public function test_calculateTotalValue(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'total_quantity' => 100.0,
            'consumed_quantity' => 20,
            'cost_per_unit' => 5.50,
        ]);

        $result = $this->inventoryService->calculateTotalValue($consumable);

        $this->assertEquals(440.0, $result); // (100 - 20) * 5.50 = 80 * 5.50
    }

    public function test_getFormattedTotalWeight_for_seeds(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'seed',
            'total_quantity' => 150.0,
            'quantity_unit' => 'g',
        ]);

        $result = $this->inventoryService->getFormattedTotalWeight($consumable);

        $this->assertEquals('150.00 g', $result);
    }

    public function test_getFormattedTotalWeight_for_other_consumables(): void
    {
        $consumable = Consumable::factory()->create([
            'type' => 'soil',
            'initial_stock' => 50,
            'consumed_quantity' => 10,
            'quantity_per_unit' => 2.5,
            'quantity_unit' => 'kg',
        ]);

        $result = $this->inventoryService->getFormattedTotalWeight($consumable);

        $this->assertEquals('100.00 kg', $result); // 40 * 2.5
    }
}
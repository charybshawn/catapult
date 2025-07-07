<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InventoryManagementService;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LotInventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryManagementService $lotInventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lotInventoryService = app(InventoryManagementService::class);
        
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

    /**
     * Helper method to create a seed consumable with proper relationships.
     */
    private function createSeedConsumable(array $attributes = []): Consumable
    {
        $seedType = ConsumableType::where('code', 'seed')->first();
        
        return Consumable::factory()->create(array_merge([
            'type' => 'seed',
            'consumable_type_id' => $seedType->id,
        ], $attributes));
    }

    public function test_lot_exists_returns_true_for_existing_lot(): void
    {
        $this->createSeedConsumable([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'is_active' => true,
        ]);

        $result = $this->lotInventoryService->lotExists('LOT001');

        $this->assertTrue($result);
    }

    public function test_lot_exists_returns_false_for_non_existent_lot(): void
    {
        $result = $this->lotInventoryService->lotExists('NON_EXISTENT_LOT');

        $this->assertFalse($result);
    }

    public function test_get_lot_quantity_returns_sum_of_all_entries(): void
    {
        // Create multiple entries for the same lot
        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'consumed_quantity' => 20.0,
            'is_active' => true,
        ]);

        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 150.0,
            'consumed_quantity' => 30.0,
            'is_active' => true,
        ]);

        $result = $this->lotInventoryService->getLotQuantity('LOT001');

        // (100 - 20) + (150 - 30) = 80 + 120 = 200
        $this->assertEquals(200.0, $result);
    }

    public function test_get_lot_quantity_returns_zero_for_non_existent_lot(): void
    {
        $result = $this->lotInventoryService->getLotQuantity('NON_EXISTENT_LOT');

        $this->assertEquals(0.0, $result);
    }

    public function test_is_lot_depleted_returns_true_when_quantity_is_zero(): void
    {
        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'consumed_quantity' => 100.0,
            'is_active' => true,
        ]);

        $result = $this->lotInventoryService->isLotDepleted('LOT001');

        $this->assertTrue($result);
    }

    public function test_is_lot_depleted_returns_false_when_quantity_available(): void
    {
        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'consumed_quantity' => 50.0,
            'is_active' => true,
        ]);

        $result = $this->lotInventoryService->isLotDepleted('LOT001');

        $this->assertFalse($result);
    }

    public function test_get_entries_in_lot_returns_chronological_order(): void
    {
        // Create entries at different times
        $older = Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'created_at' => now()->subDays(10),
            'is_active' => true,
        ]);

        $newer = Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 150.0,
            'created_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        $entries = $this->lotInventoryService->getEntriesInLot('LOT001');

        $this->assertCount(2, $entries);
        $this->assertEquals($older->id, $entries->first()->id); // Oldest first (FIFO)
        $this->assertEquals($newer->id, $entries->last()->id);
    }

    public function test_get_oldest_entry_in_lot_returns_earliest_created(): void
    {
        $older = Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'created_at' => now()->subDays(10),
            'is_active' => true,
        ]);

        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 150.0,
            'created_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        $oldest = $this->lotInventoryService->getOldestEntryInLot('LOT001');

        $this->assertEquals($older->id, $oldest->id);
    }

    public function test_get_oldest_entry_in_lot_returns_null_for_empty_lot(): void
    {
        $oldest = $this->lotInventoryService->getOldestEntryInLot('NON_EXISTENT_LOT');

        $this->assertNull($oldest);
    }

    public function test_get_lot_summary_returns_complete_information(): void
    {
        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'consumed_quantity' => 20.0,
            'created_at' => now()->subDays(10),
            'is_active' => true,
        ]);

        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 150.0,
            'consumed_quantity' => 30.0,
            'created_at' => now()->subDays(5),
            'is_active' => true,
        ]);

        $summary = $this->lotInventoryService->getLotSummary('LOT001');

        $this->assertEquals(250.0, $summary['total']); // 100 + 150
        $this->assertEquals(50.0, $summary['consumed']); // 20 + 30
        $this->assertEquals(200.0, $summary['available']); // (100-20) + (150-30)
        $this->assertEquals(2, $summary['entry_count']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $summary['oldest_entry_date']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $summary['newest_entry_date']);
    }

    public function test_get_lot_summary_handles_empty_lot(): void
    {
        $summary = $this->lotInventoryService->getLotSummary('NON_EXISTENT_LOT');

        $this->assertEquals(0.0, $summary['total']);
        $this->assertEquals(0.0, $summary['consumed']);
        $this->assertEquals(0.0, $summary['available']);
        $this->assertEquals(0, $summary['entry_count']);
        $this->assertNull($summary['oldest_entry_date']);
        $this->assertNull($summary['newest_entry_date']);
    }

    public function test_get_all_lot_numbers_returns_unique_lot_numbers(): void
    {
        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'is_active' => true,
        ]);

        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001', // Duplicate lot number
            'is_active' => true,
        ]);

        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT002',
            'is_active' => true,
        ]);

        $lotNumbers = $this->lotInventoryService->getAllLotNumbers();

        $this->assertCount(2, $lotNumbers);
        $this->assertContains('LOT001', $lotNumbers);
        $this->assertContains('LOT002', $lotNumbers);
    }

    public function test_filters_only_active_consumables(): void
    {
        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'consumed_quantity' => 0.0,
            'is_active' => true,
        ]);

        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 50.0,
            'consumed_quantity' => 0.0,
            'is_active' => false, // Inactive
        ]);

        $quantity = $this->lotInventoryService->getLotQuantity('LOT001');

        $this->assertEquals(100.0, $quantity); // Only active entry counted
    }

    public function test_filters_only_seed_type_consumables(): void
    {
        Consumable::factory()->seed()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 100.0,
            'consumed_quantity' => 0.0,
            'is_active' => true,
        ]);

        Consumable::factory()->soil()->create([
            'lot_no' => 'LOT001',
            'total_quantity' => 50.0,
            'consumed_quantity' => 0.0,
            'is_active' => true,
        ]);

        $quantity = $this->lotInventoryService->getLotQuantity('LOT001');

        $this->assertEquals(100.0, $quantity); // Only seed type counted
    }
}
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Recipe;
use App\Models\Consumable;
use App\Models\ConsumableType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeLotMethodsTest extends TestCase
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
    }

    public function test_lot_consumables_relationship_returns_correct_consumables(): void
    {
        // Create consumables for the lot
        $lotConsumable1 = Consumable::factory()->seed()->create([
            'lot_no' => 'TEST_LOT',
            'total_quantity' => 500.0,
            'is_active' => true,
        ]);

        $lotConsumable2 = Consumable::factory()->seed()->create([
            'lot_no' => 'TEST_LOT',
            'total_quantity' => 300.0,
            'is_active' => true,
        ]);

        // Create consumable for different lot (should not be included)
        $otherLotConsumable = Consumable::factory()->seed()->create([
            'lot_no' => 'OTHER_LOT',
            'total_quantity' => 200.0,
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'TEST_LOT',
            'is_active' => true,
        ]);

        $lotConsumables = $recipe->lotConsumables();

        $this->assertCount(2, $lotConsumables);
        $this->assertTrue($lotConsumables->contains($lotConsumable1));
        $this->assertTrue($lotConsumables->contains($lotConsumable2));
        $this->assertFalse($lotConsumables->contains($otherLotConsumable));
    }

    public function test_available_lot_consumables_filters_inactive(): void
    {
        // Create active and inactive consumables for the same lot
        $activeConsumable = Consumable::factory()->seed()->create([
            'lot_no' => 'TEST_LOT',
            'total_quantity' => 500.0,
            'is_active' => true,
        ]);

        $inactiveConsumable = Consumable::factory()->seed()->create([
            'lot_no' => 'TEST_LOT',
            'total_quantity' => 300.0,
            'is_active' => false,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'TEST_LOT',
            'is_active' => true,
        ]);

        $availableConsumables = $recipe->availableLotConsumables();

        $this->assertCount(1, $availableConsumables);
        $this->assertTrue($availableConsumables->contains($activeConsumable));
        $this->assertFalse($availableConsumables->contains($inactiveConsumable));
    }

    public function test_get_lot_quantity_returns_sum_of_available_stock(): void
    {
        // Create multiple consumables for the lot
        Consumable::factory()->seed()->create([
            'lot_no' => 'QUANTITY_LOT',
            'total_quantity' => 1000.0,
            'consumed_quantity' => 200.0, // 800 available
            'is_active' => true,
        ]);

        Consumable::factory()->seed()->create([
            'lot_no' => 'QUANTITY_LOT',
            'total_quantity' => 500.0,
            'consumed_quantity' => 100.0, // 400 available
            'is_active' => true,
        ]);

        // Create inactive consumable (should not be counted)
        Consumable::factory()->seed()->create([
            'lot_no' => 'QUANTITY_LOT',
            'total_quantity' => 300.0,
            'consumed_quantity' => 0, // 300 available but inactive
            'is_active' => false,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'QUANTITY_LOT',
            'is_active' => true,
        ]);

        $quantity = $recipe->getLotQuantity();

        // Should return 800 + 400 = 1200 (inactive consumable not counted)
        $this->assertEquals(1200.0, $quantity);
    }

    public function test_get_lot_quantity_returns_zero_for_non_existent_lot(): void
    {
        $recipe = Recipe::factory()->create([
            'lot_number' => 'NON_EXISTENT_LOT',
            'is_active' => true,
        ]);

        $quantity = $recipe->getLotQuantity();

        $this->assertEquals(0.0, $quantity);
    }

    public function test_is_lot_depleted_returns_true_when_no_stock(): void
    {
        // Create fully consumed consumables
        Consumable::factory()->seed()->create([
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 500.0,
            'consumed_quantity' => 500.0, // Fully consumed
            'is_active' => true,
        ]);

        Consumable::factory()->seed()->create([
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 300.0,
            'consumed_quantity' => 300.0, // Fully consumed
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'DEPLETED_LOT',
            'is_active' => true,
        ]);

        $this->assertTrue($recipe->isLotDepleted());
    }

    public function test_is_lot_depleted_returns_false_when_stock_available(): void
    {
        Consumable::factory()->seed()->create([
            'lot_no' => 'AVAILABLE_LOT',
            'total_quantity' => 500.0,
            'consumed_quantity' => 200.0, // 300 still available
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'AVAILABLE_LOT',
            'is_active' => true,
        ]);

        $this->assertFalse($recipe->isLotDepleted());
    }

    public function test_is_lot_depleted_handles_non_existent_lot(): void
    {
        $recipe = Recipe::factory()->create([
            'lot_number' => 'NON_EXISTENT_LOT',
            'is_active' => true,
        ]);

        // Should return true if lot doesn't exist
        $this->assertTrue($recipe->isLotDepleted());
    }

    public function test_can_execute_validates_sufficient_stock(): void
    {
        Consumable::factory()->seed()->create([
            'lot_no' => 'EXECUTE_LOT',
            'total_quantity' => 1000.0,
            'consumed_quantity' => 300.0, // 700 available
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'EXECUTE_LOT',
            'is_active' => true,
        ]);

        // Should be able to execute with amounts less than available
        $this->assertTrue($recipe->canExecute(500.0));
        $this->assertTrue($recipe->canExecute(700.0)); // Exact amount
        
        // Should not be able to execute with amounts greater than available
        $this->assertFalse($recipe->canExecute(701.0));
        $this->assertFalse($recipe->canExecute(1000.0));
    }

    public function test_can_execute_returns_false_for_depleted_lot(): void
    {
        // Create fully depleted lot
        Consumable::factory()->seed()->create([
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 500.0,
            'consumed_quantity' => 500.0,
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'DEPLETED_LOT',
            'is_active' => true,
        ]);

        $this->assertFalse($recipe->canExecute(1.0));
        $this->assertFalse($recipe->canExecute(0.1));
    }

    public function test_can_execute_returns_false_for_non_existent_lot(): void
    {
        $recipe = Recipe::factory()->create([
            'lot_number' => 'NON_EXISTENT_LOT',
            'is_active' => true,
        ]);

        $this->assertFalse($recipe->canExecute(1.0));
    }

    public function test_mark_lot_depleted_sets_timestamp(): void
    {
        $recipe = Recipe::factory()->create([
            'lot_number' => 'MARK_DEPLETED_LOT',
            'lot_depleted_at' => null,
            'is_active' => true,
        ]);

        $this->assertNull($recipe->lot_depleted_at);

        $recipe->markLotDepleted();

        $this->assertNotNull($recipe->lot_depleted_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $recipe->lot_depleted_at);
        
        // Should be very recent (within last minute)
        $this->assertTrue($recipe->lot_depleted_at->diffInSeconds(now()) < 60);
    }

    public function test_mark_lot_depleted_updates_existing_timestamp(): void
    {
        $originalTime = now()->subHours(2);
        
        $recipe = Recipe::factory()->create([
            'lot_number' => 'UPDATE_DEPLETED_LOT',
            'lot_depleted_at' => $originalTime,
            'is_active' => true,
        ]);

        $recipe->markLotDepleted();

        $this->assertNotNull($recipe->lot_depleted_at);
        $this->assertNotEquals($originalTime->toDateTimeString(), $recipe->lot_depleted_at->toDateTimeString());
        $this->assertTrue($recipe->lot_depleted_at->diffInSeconds(now()) < 60);
    }

    public function test_recipe_model_fillable_includes_lot_fields(): void
    {
        $recipe = new Recipe();
        $fillable = $recipe->getFillable();

        $this->assertContains('lot_number', $fillable);
        $this->assertContains('lot_depleted_at', $fillable);
    }

    public function test_lot_depleted_at_is_cast_to_datetime(): void
    {
        $recipe = Recipe::factory()->create([
            'lot_number' => 'CAST_TEST_LOT',
            'lot_depleted_at' => '2025-01-01 12:00:00',
            'is_active' => true,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $recipe->lot_depleted_at);
        $this->assertEquals('2025-01-01 12:00:00', $recipe->lot_depleted_at->format('Y-m-d H:i:s'));
    }

    public function test_recipe_without_lot_number_returns_zero_quantity(): void
    {
        $recipe = Recipe::factory()->create([
            'lot_number' => null, // No lot number
            'is_active' => true,
        ]);

        $this->assertEquals(0.0, $recipe->getLotQuantity());
        $this->assertTrue($recipe->isLotDepleted());
        $this->assertFalse($recipe->canExecute(1.0));
    }

    public function test_lot_relationships_handle_empty_lot_number(): void
    {
        $recipe = Recipe::factory()->create([
            'lot_number' => '', // Empty string
            'is_active' => true,
        ]);

        $this->assertEquals(0, $recipe->lotConsumables()->count());
        $this->assertEquals(0, $recipe->availableLotConsumables()->count());
    }

    public function test_recipe_respects_seed_type_filtering(): void
    {
        // Create seed consumable for the lot
        $seedConsumable = Consumable::factory()->seed()->create([
            'lot_no' => 'MIXED_LOT',
            'total_quantity' => 500.0,
            'is_active' => true,
        ]);

        // Create soil consumable with same lot number (should be ignored)
        $soilConsumable = Consumable::factory()->create([
            'type' => 'soil',
            'lot_no' => 'MIXED_LOT',
            'total_quantity' => 300.0,
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'lot_number' => 'MIXED_LOT',
            'is_active' => true,
        ]);

        $lotConsumables = $recipe->lotConsumables();

        $this->assertCount(1, $lotConsumables);
        $this->assertTrue($lotConsumables->contains($seedConsumable));
        $this->assertFalse($lotConsumables->contains($soilConsumable));
        
        $this->assertEquals(500.0, $recipe->getLotQuantity());
    }
}
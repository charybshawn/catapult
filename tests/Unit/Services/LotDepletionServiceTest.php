<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\LotDepletionService;
use App\Services\LotInventoryService;
use App\Models\Recipe;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class LotDepletionServiceTest extends TestCase
{
    use RefreshDatabase;

    private LotDepletionService $lotDepletionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lotDepletionService = new LotDepletionService();
        
        // Create required lookup data
        ConsumableType::factory()->create([
            'code' => 'seed',
            'name' => 'Seeds',
            'is_active' => true,
        ]);
    }

    public function test_check_all_lots_returns_comprehensive_summary(): void
    {
        // Create test data with various lot conditions
        $this->createTestLots();

        $summary = $this->lotDepletionService->checkAllLots();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('total_lots', $summary);
        $this->assertArrayHasKey('active_lots', $summary);
        $this->assertArrayHasKey('depleted_lots', $summary);
        $this->assertArrayHasKey('low_stock_lots', $summary);
        $this->assertArrayHasKey('details', $summary);
    }

    public function test_get_depleted_recipes_returns_only_depleted(): void
    {
        // Create a depleted lot with recipe
        $depletedConsumable = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 100.0, // Fully consumed
            'is_active' => true,
        ]);

        $depletedRecipe = Recipe::factory()->create([
            'name' => 'Depleted Recipe',
            'lot_number' => 'DEPLETED_LOT',
            'is_active' => true,
        ]);

        // Create a healthy lot with recipe
        $healthyConsumable = Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'HEALTHY_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 20.0, // Still has stock
            'is_active' => true,
        ]);

        $healthyRecipe = Recipe::factory()->create([
            'name' => 'Healthy Recipe',
            'lot_number' => 'HEALTHY_LOT',
            'is_active' => true,
        ]);

        $depletedRecipes = $this->lotDepletionService->getDepletedRecipes();

        $this->assertCount(1, $depletedRecipes);
        $this->assertEquals($depletedRecipe->id, $depletedRecipes->first()->id);
    }

    public function test_get_low_stock_lots_identifies_below_threshold(): void
    {
        // Create a low stock lot (below 50g threshold)
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'LOW_STOCK_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 60.0, // 40g remaining - below 50g threshold
            'is_active' => true,
        ]);

        // Create a healthy stock lot
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'HEALTHY_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 20.0, // 80g remaining - above 50g threshold
            'is_active' => true,
        ]);

        $lowStockLots = $this->lotDepletionService->getLowStockLots();

        $this->assertCount(1, $lowStockLots);
        $this->assertEquals('LOW_STOCK_LOT', $lowStockLots[0]['lot_number']);
        $this->assertEquals(40.0, $lowStockLots[0]['available_quantity']);
    }

    public function test_send_depletion_alerts_with_notification_mocking(): void
    {
        Notification::fake();

        // Create admin user
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        // Create depleted lot and recipe
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 100.0,
            'is_active' => true,
        ]);

        Recipe::factory()->create([
            'name' => 'Depleted Recipe',
            'lot_number' => 'DEPLETED_LOT',
            'is_active' => true,
        ]);

        $result = $this->lotDepletionService->sendDepletionAlerts();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('notifications_sent', $result);
        $this->assertArrayHasKey('depleted_recipes', $result);
    }

    public function test_mark_depleted_lots_updates_recipe_timestamps(): void
    {
        // Create depleted lot and recipe
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 100.0,
            'is_active' => true,
        ]);

        $recipe = Recipe::factory()->create([
            'name' => 'Test Recipe',
            'lot_number' => 'DEPLETED_LOT',
            'lot_depleted_at' => null, // Not yet marked
            'is_active' => true,
        ]);

        $markedRecipes = $this->lotDepletionService->markDepletedLots();

        $this->assertCount(1, $markedRecipes);
        
        $recipe->refresh();
        $this->assertNotNull($recipe->lot_depleted_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $recipe->lot_depleted_at);
    }

    public function test_does_not_mark_already_marked_recipes(): void
    {
        // Create depleted lot and already marked recipe
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 100.0,
            'is_active' => true,
        ]);

        $alreadyMarked = now()->subHours(2);
        $recipe = Recipe::factory()->create([
            'name' => 'Already Marked Recipe',
            'lot_number' => 'DEPLETED_LOT',
            'lot_depleted_at' => $alreadyMarked, // Already marked
            'is_active' => true,
        ]);

        $markedRecipes = $this->lotDepletionService->markDepletedLots();

        $this->assertCount(0, $markedRecipes); // Should not mark again
        
        $recipe->refresh();
        $this->assertEquals($alreadyMarked->toDateTimeString(), $recipe->lot_depleted_at->toDateTimeString());
    }

    public function test_get_lot_status_summary_provides_detailed_breakdown(): void
    {
        $this->createTestLots();

        $summary = $this->lotDepletionService->getLotStatusSummary();

        $this->assertIsArray($summary);
        $this->assertGreaterThan(0, $summary['total_lots']);
        $this->assertArrayHasKey('depleted_count', $summary);
        $this->assertArrayHasKey('low_stock_count', $summary);
        $this->assertArrayHasKey('healthy_count', $summary);
        $this->assertArrayHasKey('lot_details', $summary);
    }

    public function test_filters_only_active_recipes(): void
    {
        // Create depleted lot
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 100.0,
            'is_active' => true,
        ]);

        // Create active and inactive recipes for the same lot
        $activeRecipe = Recipe::factory()->create([
            'name' => 'Active Recipe',
            'lot_number' => 'DEPLETED_LOT',
            'is_active' => true,
        ]);

        $inactiveRecipe = Recipe::factory()->create([
            'name' => 'Inactive Recipe',
            'lot_number' => 'DEPLETED_LOT',
            'is_active' => false,
        ]);

        $depletedRecipes = $this->lotDepletionService->getDepletedRecipes();

        $this->assertCount(1, $depletedRecipes);
        $this->assertEquals($activeRecipe->id, $depletedRecipes->first()->id);
    }

    public function test_handles_lots_without_recipes(): void
    {
        // Create depleted lot without any recipes
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'ORPHAN_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 100.0,
            'is_active' => true,
        ]);

        $summary = $this->lotDepletionService->checkAllLots();

        // Should still track the lot even without recipes
        $this->assertGreaterThan(0, $summary['total_lots']);
        $this->assertGreaterThan(0, $summary['depleted_lots']);
    }

    private function createTestLots(): void
    {
        // Create a depleted lot
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'DEPLETED_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 100.0,
            'is_active' => true,
        ]);

        // Create a low stock lot
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'LOW_STOCK_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 70.0, // 30g remaining
            'is_active' => true,
        ]);

        // Create a healthy lot
        Consumable::factory()->create([
            'type' => 'seed',
            'lot_no' => 'HEALTHY_LOT',
            'total_quantity' => 100.0,
            'consumed_quantity' => 20.0, // 80g remaining
            'is_active' => true,
        ]);

        // Create recipes for the lots
        Recipe::factory()->create([
            'name' => 'Depleted Recipe',
            'lot_number' => 'DEPLETED_LOT',
            'is_active' => true,
        ]);

        Recipe::factory()->create([
            'name' => 'Low Stock Recipe',
            'lot_number' => 'LOW_STOCK_LOT',
            'is_active' => true,
        ]);

        Recipe::factory()->create([
            'name' => 'Healthy Recipe',
            'lot_number' => 'HEALTHY_LOT',
            'is_active' => true,
        ]);
    }
}
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RecipeService;
use App\Services\InventoryManagementService;
use App\Models\Recipe;
use App\Models\Consumable;
use App\Models\ConsumableType;
use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeServiceWithMockingTest extends TestCase
{
    use RefreshDatabase;

    private RecipeService $recipeService;
    private $mockInventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock of InventoryManagementService
        $this->mockInventoryService = Mockery::mock(InventoryManagementService::class);
        
        // Bind the mock to the container
        $this->app->instance(InventoryManagementService::class, $this->mockInventoryService);
        
        // Now when RecipeService is resolved, it will get the mock
        $this->recipeService = $this->app->make(RecipeService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_checks_if_recipe_can_be_executed_with_mocked_inventory(): void
    {
        // Create test data
        $recipe = Recipe::factory()->create([
            'lot_number' => 'TEST123',
            'lot_depleted_at' => null,
        ]);

        // Set up mock expectations
        $this->mockInventoryService
            ->shouldReceive('isLotDepleted')
            ->once()
            ->with('TEST123')
            ->andReturn(false);
            
        $this->mockInventoryService
            ->shouldReceive('getLotQuantity')
            ->once()
            ->with('TEST123')
            ->andReturn(100.0);

        // Test the method
        $result = $this->recipeService->canExecuteRecipe($recipe, 50.0);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_auto_marks_lot_depleted_when_inventory_shows_depleted(): void
    {
        // Create test data
        $recipe = Recipe::factory()->create([
            'lot_number' => 'TEST123',
            'lot_depleted_at' => null,
        ]);

        // Set up mock expectations
        $this->mockInventoryService
            ->shouldReceive('isLotDepleted')
            ->once()
            ->with('TEST123')
            ->andReturn(true);

        // Test the method
        $result = $this->recipeService->canExecuteRecipe($recipe, 50.0);

        // Assert
        $this->assertFalse($result);
        
        // Verify the recipe was marked as depleted
        $recipe->refresh();
        $this->assertNotNull($recipe->lot_depleted_at);
    }

    /** @test */
    public function it_generates_recipe_name_correctly(): void
    {
        // Create seed type
        $seedType = ConsumableType::factory()->create(['code' => 'seed']);
        
        // Create a consumable that matches the expected format
        $consumable = Consumable::factory()->create([
            'name' => 'Basil (Genovese)',
            'consumable_type_id' => $seedType->id,
            'lot_no' => 'LOT001',
            'is_active' => true,
        ]);

        // Create recipe
        $recipe = Recipe::factory()->make([
            'lot_number' => 'LOT001',
            'seed_density_grams_per_tray' => 10.5,
            'days_to_maturity' => 21,
            'name' => null,
        ]);

        // Generate name
        $this->recipeService->generateRecipeName($recipe);

        // Assert
        $this->assertEquals('Basil (Genovese) - 10.5G - 21 DTM - LOT001', $recipe->name);
        $this->assertEquals('Basil', $recipe->common_name);
        $this->assertEquals('Genovese', $recipe->cultivar_name);
    }
}
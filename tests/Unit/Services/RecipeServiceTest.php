<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RecipeService;
use App\Models\Recipe;
use App\Models\Consumable;
use App\Models\ConsumableType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecipeServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecipeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecipeService();
    }

    /** @test */
    public function it_generates_recipe_name_correctly(): void
    {
        // Create seed type
        $seedType = ConsumableType::create([
            'code' => 'seed',
            'name' => 'Seed',
            'is_active' => true,
        ]);

        // Create consumable
        Consumable::create([
            'name' => 'Kale (Red Russian)',
            'consumable_type_id' => $seedType->id,
            'lot_no' => 'KRR123',
            'total_quantity' => 100,
            'consumed_quantity' => 0,
            'is_active' => true,
        ]);

        // Create recipe
        $recipe = new Recipe([
            'lot_number' => 'KRR123',
            'seed_density_grams_per_tray' => 25.5,
            'days_to_maturity' => 14,
        ]);

        $this->service->generateRecipeName($recipe);

        $this->assertEquals('Kale (Red Russian) - 25.5G - 14 DTM - KRR123', $recipe->name);
        $this->assertEquals('Kale', $recipe->common_name);
        $this->assertEquals('Red Russian', $recipe->cultivar_name);
    }

    /** @test */
    public function it_ensures_unique_recipe_names(): void
    {
        // Create existing recipes
        Recipe::factory()->create(['name' => 'Test Recipe']);
        Recipe::factory()->create(['name' => 'Test Recipe (2)']);

        $recipe = new Recipe();
        
        $uniqueName = $this->service->ensureUniqueRecipeName($recipe, 'Test Recipe');
        $this->assertEquals('Test Recipe (3)', $uniqueName);
    }

    /** @test */
    public function it_calculates_total_days_correctly(): void
    {
        // Test with days_to_maturity set
        $recipe = new Recipe([
            'days_to_maturity' => 14.5,
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 8,
        ]);

        $this->assertEquals(14.5, $this->service->calculateTotalDays($recipe));

        // Test without days_to_maturity
        $recipe->days_to_maturity = null;
        $this->assertEquals(13.0, $this->service->calculateTotalDays($recipe));
    }

    /** @test */
    public function it_calculates_effective_total_days_with_soak_time(): void
    {
        $recipe = new Recipe([
            'days_to_maturity' => 14,
            'seed_soak_hours' => 12,
        ]);

        $this->assertEquals(14.5, $this->service->calculateEffectiveTotalDays($recipe));
    }

    /** @test */
    public function it_validates_recipe_correctly(): void
    {
        // Valid recipe
        $recipe = new Recipe([
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 8,
            'seed_density_grams_per_tray' => 25,
            'expected_yield_grams' => 200,
            'buffer_percentage' => 10,
        ]);

        $errors = $this->service->validateRecipe($recipe);
        $this->assertEmpty($errors);

        // Invalid recipe
        $recipe = new Recipe([
            'germination_days' => -1,
            'blackout_days' => -2,
            'light_days' => -3,
            'seed_density_grams_per_tray' => 0,
            'expected_yield_grams' => -100,
            'buffer_percentage' => 150,
        ]);

        $errors = $this->service->validateRecipe($recipe);
        $this->assertCount(6, $errors);
        $this->assertContains('Germination days cannot be negative', $errors);
        $this->assertContains('Blackout days cannot be negative', $errors);
        $this->assertContains('Light days cannot be negative', $errors);
        $this->assertContains('Seed density must be greater than zero', $errors);
        $this->assertContains('Expected yield must be greater than zero', $errors);
        $this->assertContains('Buffer percentage must be between 0 and 100', $errors);
    }
}
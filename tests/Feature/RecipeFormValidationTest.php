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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

class RecipeFormValidationTest extends TestCase
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
    }

    /** @test */
    public function recipe_requires_essential_fields(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Attempt to create recipe without required name field
        Recipe::create([
            'lot_number' => 'TEST001',
            'seed_density_grams_per_tray' => 25.0,
        ]);
    }

    /** @test */
    public function recipe_validates_numeric_fields_correctly(): void
    {
        // Create valid recipe with numeric fields
        $recipe = Recipe::factory()->create([
            'seed_density_grams_per_tray' => 25.5,
            'days_to_maturity' => 14.0,
            'germination_days' => 3.5,
            'blackout_days' => 2.0,
            'light_days' => 8.5,
            'seed_soak_hours' => 12,
            'expected_yield_grams' => 180.75,
            'buffer_percentage' => 10.25,
            'suspend_water_hours' => 24,
        ]);

        $this->assertIsFloat($recipe->seed_density_grams_per_tray);
        $this->assertEquals(25.5, $recipe->seed_density_grams_per_tray);
        
        $this->assertIsFloat($recipe->days_to_maturity);
        $this->assertEquals(14.0, $recipe->days_to_maturity);
        
        $this->assertIsInt($recipe->seed_soak_hours);
        $this->assertEquals(12, $recipe->seed_soak_hours);
        
        $this->assertIsInt($recipe->suspend_water_hours);
        $this->assertEquals(24, $recipe->suspend_water_hours);
    }

    /** @test */
    public function recipe_validates_positive_values_for_quantities(): void
    {
        // Should accept zero and positive values
        $recipe = Recipe::factory()->create([
            'seed_density_grams_per_tray' => 0.0,
            'days_to_maturity' => 1.0,
            'germination_days' => 0.0,
            'blackout_days' => 0.0,
            'light_days' => 1.0,
            'seed_soak_hours' => 0,
            'expected_yield_grams' => 0.0,
            'buffer_percentage' => 0.0,
            'suspend_water_hours' => 0,
        ]);

        $this->assertEquals(0.0, $recipe->seed_density_grams_per_tray);
        $this->assertEquals(1.0, $recipe->days_to_maturity);
        $this->assertEquals(0, $recipe->seed_soak_hours);
    }

    /** @test */
    public function recipe_validates_lot_number_format(): void
    {
        // Should accept various lot number formats
        $validLotNumbers = [
            'LOT001',
            'ABC123',
            'TEST-2025-001',
            'MX_001_2025',
            'L123',
            'BATCH_A1',
        ];

        foreach ($validLotNumbers as $lotNumber) {
            $recipe = Recipe::factory()->create([
                'lot_number' => $lotNumber,
            ]);
            
            $this->assertEquals($lotNumber, $recipe->lot_number);
        }
    }

    /** @test */
    public function recipe_handles_null_optional_fields(): void
    {
        $recipe = Recipe::factory()->create([
            'seed_entry_id' => null,
            'seed_consumable_id' => null,
            'supplier_soil_id' => null,
            'soil_consumable_id' => null,
            'expected_yield_grams' => null,
            'notes' => null,
            'lot_depleted_at' => null,
        ]);

        $this->assertNull($recipe->seed_entry_id);
        $this->assertNull($recipe->seed_consumable_id);
        $this->assertNull($recipe->supplier_soil_id);
        $this->assertNull($recipe->soil_consumable_id);
        $this->assertNull($recipe->expected_yield_grams);
        $this->assertNull($recipe->notes);
        $this->assertNull($recipe->lot_depleted_at);
    }

    /** @test */
    public function recipe_validates_relationship_constraints(): void
    {
        // Create related models
        $seedEntry = SeedEntry::factory()->create();
        $supplier = Supplier::factory()->create();
        $soilConsumable = Consumable::factory()->create(['type' => 'soil']);

        // Create recipe with valid relationships
        $recipe = Recipe::factory()->create([
            'seed_entry_id' => $seedEntry->id,
            'supplier_soil_id' => $supplier->id,
            'soil_consumable_id' => $soilConsumable->id,
        ]);

        // Verify relationships work
        $this->assertInstanceOf(SeedEntry::class, $recipe->seedEntry);
        $this->assertInstanceOf(Supplier::class, $recipe->soilSupplier);
        $this->assertInstanceOf(Consumable::class, $recipe->soilConsumable);
        
        $this->assertEquals($seedEntry->id, $recipe->seedEntry->id);
        $this->assertEquals($supplier->id, $recipe->soilSupplier->id);
        $this->assertEquals($soilConsumable->id, $recipe->soilConsumable->id);
    }

    /** @test */
    public function recipe_validates_stage_day_calculations(): void
    {
        $recipe = Recipe::factory()->create([
            'days_to_maturity' => 14.0,
            'germination_days' => 3.0,
            'blackout_days' => 2.0,
            'light_days' => 9.0,
        ]);

        // Test that total stages equal DTM
        $totalStages = $recipe->germination_days + $recipe->blackout_days + $recipe->light_days;
        $this->assertEquals($recipe->days_to_maturity, $totalStages);

        // Test that totalDays() method works correctly
        $this->assertEquals(14.0, $recipe->totalDays());
    }

    /** @test */
    public function recipe_validates_stage_day_calculations_when_stages_dont_match_dtm(): void
    {
        // Create recipe where stages don't add up to DTM
        $recipe = Recipe::factory()->create([
            'days_to_maturity' => 14.0,
            'germination_days' => 3.0,
            'blackout_days' => 2.0,
            'light_days' => 8.0, // Only adds to 13, not 14
        ]);

        // totalDays() should prefer DTM when set
        $this->assertEquals(14.0, $recipe->totalDays());

        // But individual stages should retain their values
        $this->assertEquals(3.0, $recipe->germination_days);
        $this->assertEquals(2.0, $recipe->blackout_days);
        $this->assertEquals(8.0, $recipe->light_days);
        
        $totalStages = $recipe->germination_days + $recipe->blackout_days + $recipe->light_days;
        $this->assertEquals(13.0, $totalStages);
    }

    /** @test */
    public function recipe_validates_boolean_fields(): void
    {
        // Test various boolean inputs
        $recipe1 = Recipe::factory()->create(['is_active' => true]);
        $this->assertTrue($recipe1->is_active);

        $recipe2 = Recipe::factory()->create(['is_active' => false]);
        $this->assertFalse($recipe2->is_active);

        $recipe3 = Recipe::factory()->create(['is_active' => 1]);
        $this->assertTrue($recipe3->is_active);

        $recipe4 = Recipe::factory()->create(['is_active' => 0]);
        $this->assertFalse($recipe4->is_active);

        $recipe5 = Recipe::factory()->create(['is_active' => '1']);
        $this->assertTrue($recipe5->is_active);

        $recipe6 = Recipe::factory()->create(['is_active' => '0']);
        $this->assertFalse($recipe6->is_active);
    }

    /** @test */
    public function recipe_validates_text_field_lengths(): void
    {
        // Test maximum lengths for text fields
        $longName = str_repeat('A', 255);
        $tooLongName = str_repeat('A', 256);

        // Should accept 255 characters
        $recipe = Recipe::factory()->create([
            'name' => $longName,
            'common_name' => $longName,
            'cultivar_name' => $longName,
        ]);

        $this->assertEquals($longName, $recipe->name);
        $this->assertEquals($longName, $recipe->common_name);
        $this->assertEquals($longName, $recipe->cultivar_name);

        // Test notes can be longer (text field)
        $longNotes = str_repeat('This is a long note. ', 100); // ~2000 characters
        $recipe2 = Recipe::factory()->create([
            'notes' => $longNotes,
        ]);

        $this->assertEquals($longNotes, $recipe2->notes);
    }

    /** @test */
    public function recipe_validates_decimal_precision(): void
    {
        $recipe = Recipe::factory()->create([
            'seed_density_grams_per_tray' => 25.123,
            'days_to_maturity' => 14.567,
            'germination_days' => 3.789,
            'expected_yield_grams' => 180.456,
            'buffer_percentage' => 10.12345, // Should be rounded to 2 decimal places
        ]);

        // Most fields should retain precision
        $this->assertEquals(25.123, $recipe->seed_density_grams_per_tray);
        $this->assertEquals(14.567, $recipe->days_to_maturity);
        $this->assertEquals(3.789, $recipe->germination_days);
        $this->assertEquals(180.456, $recipe->expected_yield_grams);

        // Buffer percentage should be rounded to 2 decimal places
        $this->assertEquals(10.12, $recipe->buffer_percentage);
    }

    /** @test */
    public function recipe_validates_lot_number_case_sensitivity(): void
    {
        // Lot numbers should be stored as entered (case-sensitive)
        $recipe1 = Recipe::factory()->create(['lot_number' => 'ABC123']);
        $recipe2 = Recipe::factory()->create(['lot_number' => 'abc123']);
        $recipe3 = Recipe::factory()->create(['lot_number' => 'Abc123']);

        $this->assertEquals('ABC123', $recipe1->lot_number);
        $this->assertEquals('abc123', $recipe2->lot_number);
        $this->assertEquals('Abc123', $recipe3->lot_number);

        // All should be different
        $this->assertNotEquals($recipe1->lot_number, $recipe2->lot_number);
        $this->assertNotEquals($recipe2->lot_number, $recipe3->lot_number);
        $this->assertNotEquals($recipe1->lot_number, $recipe3->lot_number);
    }

    /** @test */
    public function recipe_validates_special_characters_in_names(): void
    {
        // Test various special characters in names
        $specialNames = [
            'Lettuce (Buttercrunch)',
            'Kale - Red Russian',
            'Basil: Genovese Variety',
            'Arugula & Rocket Mix',
            'Spinach #1 Premium',
            'Chard "Bright Lights"',
            'Microgreens 50/50 Mix',
        ];

        foreach ($specialNames as $name) {
            $recipe = Recipe::factory()->create([
                'name' => $name,
                'common_name' => $name,
                'cultivar_name' => $name,
            ]);

            $this->assertEquals($name, $recipe->name);
            $this->assertEquals($name, $recipe->common_name);
            $this->assertEquals($name, $recipe->cultivar_name);
        }
    }

    /** @test */
    public function recipe_validates_edge_case_numeric_values(): void
    {
        // Test very small positive values
        $recipe = Recipe::factory()->create([
            'seed_density_grams_per_tray' => 0.001,
            'days_to_maturity' => 0.1,
            'germination_days' => 0.5,
            'expected_yield_grams' => 0.01,
            'buffer_percentage' => 0.01,
        ]);

        $this->assertEquals(0.001, $recipe->seed_density_grams_per_tray);
        $this->assertEquals(0.1, $recipe->days_to_maturity);
        $this->assertEquals(0.5, $recipe->germination_days);
        $this->assertEquals(0.01, $recipe->expected_yield_grams);
        $this->assertEquals(0.01, $recipe->buffer_percentage);

        // Test large values
        $recipe2 = Recipe::factory()->create([
            'seed_density_grams_per_tray' => 999.999,
            'days_to_maturity' => 365.0,
            'expected_yield_grams' => 9999.99,
            'buffer_percentage' => 99.99,
        ]);

        $this->assertEquals(999.999, $recipe2->seed_density_grams_per_tray);
        $this->assertEquals(365.0, $recipe2->days_to_maturity);
        $this->assertEquals(9999.99, $recipe2->expected_yield_grams);
        $this->assertEquals(99.99, $recipe2->buffer_percentage);
    }

    /** @test */
    public function recipe_validates_datetime_fields(): void
    {
        $now = now();
        $futureDate = now()->addDays(30);
        $pastDate = now()->subDays(30);

        // Test various datetime formats
        $recipe = Recipe::factory()->create([
            'lot_depleted_at' => $now,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $recipe->lot_depleted_at);
        $this->assertEquals($now->format('Y-m-d H:i:s'), $recipe->lot_depleted_at->format('Y-m-d H:i:s'));

        // Test string datetime
        $recipe2 = Recipe::factory()->create([
            'lot_depleted_at' => '2025-01-15 14:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $recipe2->lot_depleted_at);
        $this->assertEquals('2025-01-15 14:30:00', $recipe2->lot_depleted_at->format('Y-m-d H:i:s'));

        // Test null datetime
        $recipe3 = Recipe::factory()->create([
            'lot_depleted_at' => null,
        ]);

        $this->assertNull($recipe3->lot_depleted_at);
    }

    /** @test */
    public function recipe_validates_complete_form_submission(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Create related models
        $soilConsumable = Consumable::factory()->create(['type' => 'soil']);

        // Test complete form data (as would come from the form)
        $formData = [
            'name' => 'Complete Recipe Test',
            'common_name' => 'Test Variety',
            'cultivar_name' => 'Test Cultivar',
            'lot_number' => 'COMPLETE001',
            'soil_consumable_id' => $soilConsumable->id,
            'seed_density_grams_per_tray' => 25.0,
            'seed_density' => 25.0, // Legacy field
            'days_to_maturity' => 14.0,
            'germination_days' => 3.0,
            'blackout_days' => 2.0,
            'light_days' => 9.0,
            'seed_soak_hours' => 0,
            'expected_yield_grams' => 200.0,
            'buffer_percentage' => 10.0,
            'suspend_water_hours' => 12,
            'harvest_days' => 0, // Legacy field
            'is_active' => true,
            'notes' => 'Complete recipe test with all fields',
            'seed_entry_id' => null,
            'seed_consumable_id' => null,
            'supplier_soil_id' => null,
        ];

        $recipe = Recipe::create($formData);

        // Verify all fields were saved correctly
        $this->assertEquals($formData['name'], $recipe->name);
        $this->assertEquals($formData['common_name'], $recipe->common_name);
        $this->assertEquals($formData['cultivar_name'], $recipe->cultivar_name);
        $this->assertEquals($formData['lot_number'], $recipe->lot_number);
        $this->assertEquals($formData['soil_consumable_id'], $recipe->soil_consumable_id);
        $this->assertEquals($formData['seed_density_grams_per_tray'], $recipe->seed_density_grams_per_tray);
        $this->assertEquals($formData['seed_density'], $recipe->seed_density);
        $this->assertEquals($formData['days_to_maturity'], $recipe->days_to_maturity);
        $this->assertEquals($formData['germination_days'], $recipe->germination_days);
        $this->assertEquals($formData['blackout_days'], $recipe->blackout_days);
        $this->assertEquals($formData['light_days'], $recipe->light_days);
        $this->assertEquals($formData['seed_soak_hours'], $recipe->seed_soak_hours);
        $this->assertEquals($formData['expected_yield_grams'], $recipe->expected_yield_grams);
        $this->assertEquals($formData['buffer_percentage'], $recipe->buffer_percentage);
        $this->assertEquals($formData['suspend_water_hours'], $recipe->suspend_water_hours);
        $this->assertEquals($formData['harvest_days'], $recipe->harvest_days);
        $this->assertEquals($formData['is_active'], $recipe->is_active);
        $this->assertEquals($formData['notes'], $recipe->notes);

        // Verify the recipe is saved to database
        $this->assertDatabaseHas('recipes', [
            'id' => $recipe->id,
            'name' => $formData['name'],
            'lot_number' => $formData['lot_number'],
        ]);
    }
}
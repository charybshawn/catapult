<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CropValidationService;
use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Unit tests for CropValidationService in agricultural microgreens production.
 * 
 * Tests comprehensive crop validation functionality including timestamp sequence validation,
 * crop initialization, stage timestamp adjustments, and data validation for agricultural
 * workflow management. Validates business rules and data integrity for microgreens
 * production lifecycle management.
 *
 * @covers \App\Services\CropValidationService
 * @group unit
 * @group services
 * @group crops
 * @group validation
 * @group agricultural-testing
 * 
 * @business_context Agricultural crop validation and data integrity for microgreens production
 * @test_category Unit tests for crop validation service functionality
 * @agricultural_workflow Crop lifecycle validation and business rule enforcement
 */
class CropValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CropValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CropValidationService();
        
        // Create crop stages
        CropStage::create(['code' => 'germination', 'name' => 'Germination', 'order' => 1]);
        CropStage::create(['code' => 'blackout', 'name' => 'Blackout', 'order' => 2]);
        CropStage::create(['code' => 'light', 'name' => 'Light', 'order' => 3]);
        CropStage::create(['code' => 'harvested', 'name' => 'Harvested', 'order' => 4]);
    }

    /**
     * Test timestamp sequence validation for agricultural crop growth stages.
     * 
     * Validates that crop growth stage timestamps maintain proper chronological order
     * for agricultural production workflow integrity. Tests both valid sequences and
     * invalid sequences that would violate microgreens production timelines.
     *
     * @test
     * @return void
     * @agricultural_scenario Growth stage timing validation for production workflow
     * @business_validation Ensures chronological integrity of crop growth stages
     */
    public function it_validates_timestamp_sequence_correctly(): void
    {
        // Valid sequence
        $crop = new Crop([
            'planting_at' => Carbon::parse('2025-01-01 08:00'),
            'germination_at' => Carbon::parse('2025-01-01 08:00'),
            'blackout_at' => Carbon::parse('2025-01-04 08:00'),
            'light_at' => Carbon::parse('2025-01-06 08:00'),
        ]);

        // Should not throw exception
        $this->service->validateTimestampSequence($crop);
        $this->assertTrue(true); // If we get here, validation passed

        // Invalid sequence
        $crop = new Crop([
            'planting_at' => Carbon::parse('2025-01-01 08:00'),
            'germination_at' => Carbon::parse('2025-01-01 08:00'),
            'blackout_at' => Carbon::parse('2025-01-06 08:00'), // After light_at
            'light_at' => Carbon::parse('2025-01-04 08:00'),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Growth stage timestamps must be in chronological order');
        $this->service->validateTimestampSequence($crop);
    }

    /**
     * Test new crop initialization with default agricultural parameters.
     * 
     * Validates that new crops are properly initialized with default timestamps,
     * stage information, and timing calculations for agricultural production
     * workflow management. Tests setup of initial microgreens crop state.
     *
     * @test
     * @return void
     * @agricultural_scenario New crop initialization with production defaults
     * @business_logic Default values enable immediate agricultural workflow integration
     */
    public function it_initializes_new_crop_with_defaults(): void
    {
        $crop = new Crop();
        
        $this->service->initializeNewCrop($crop);

        $this->assertNotNull($crop->planting_at);
        $this->assertNotNull($crop->germination_at);
        $this->assertEquals($crop->planting_at, $crop->germination_at);
        $this->assertNotNull($crop->current_stage_id);
        $this->assertEquals(0, $crop->time_to_next_stage_minutes);
        $this->assertEquals('Unknown', $crop->time_to_next_stage_display);
        $this->assertEquals(0, $crop->stage_age_minutes);
        $this->assertEquals('0m', $crop->stage_age_display);
        $this->assertEquals(0, $crop->total_age_minutes);
        $this->assertEquals('0m', $crop->total_age_display);
    }

    /**
     * Test stage timestamp adjustment for agricultural production schedule changes.
     * 
     * Validates that changing crop planting dates properly cascades timestamp
     * adjustments through all growth stages for agricultural production planning
     * flexibility. Tests schedule modification impact on microgreens workflows.
     *
     * @test
     * @return void
     * @agricultural_scenario Production schedule adjustment requiring date cascade
     * @business_logic Planting date changes propagate through all growth stages
     */
    public function it_adjusts_stage_timestamps_when_planting_date_changes(): void
    {
        $recipe = Recipe::factory()->create();
        
        $originalPlantingAt = Carbon::parse('2025-01-01 08:00');
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planting_at' => $originalPlantingAt,
            'germination_at' => $originalPlantingAt,
            'blackout_at' => $originalPlantingAt->copy()->addDays(3),
            'light_at' => $originalPlantingAt->copy()->addDays(5),
        ]);

        // Change planting date forward by 2 days
        $crop->planting_at = $originalPlantingAt->copy()->addDays(2);
        
        $this->service->adjustStageTimestamps($crop);

        // All timestamps should be moved forward by 2 days
        $this->assertEquals(
            $originalPlantingAt->copy()->addDays(2)->format('Y-m-d H:i:s'),
            $crop->germination_at->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $originalPlantingAt->copy()->addDays(5)->format('Y-m-d H:i:s'),
            $crop->blackout_at->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $originalPlantingAt->copy()->addDays(7)->format('Y-m-d H:i:s'),
            $crop->light_at->format('Y-m-d H:i:s')
        );
    }

    /**
     * Test comprehensive crop data validation for agricultural business rules.
     * 
     * Validates that crop data meets agricultural business requirements including
     * recipe validation, tray count constraints, harvest weight validation, and
     * stage consistency for microgreens production data integrity.
     *
     * @test
     * @return void
     * @agricultural_scenario Crop data validation against agricultural business rules
     * @business_validation Ensures crop data meets production requirements
     */
    public function it_validates_crop_data(): void
    {
        // Valid crop
        $recipe = Recipe::factory()->create();
        $crop = new Crop([
            'recipe_id' => $recipe->id,
            'tray_count' => 5,
            'harvest_weight_grams' => 200.5,
            'current_stage_id' => CropStage::where('code', 'germination')->first()->id,
        ]);

        $errors = $this->service->validateCrop($crop);
        $this->assertEmpty($errors);

        // Invalid crop
        $crop = new Crop([
            'recipe_id' => 99999, // Non-existent
            'tray_count' => -1,
            'harvest_weight_grams' => -100,
            'current_stage_id' => 99999, // Non-existent
        ]);

        $errors = $this->service->validateCrop($crop);
        $this->assertContains('Tray count must be greater than zero', $errors);
        $this->assertContains('Harvest weight cannot be negative', $errors);
        $this->assertContains('Invalid recipe selected', $errors);
        $this->assertContains('Invalid growth stage', $errors);
    }
}
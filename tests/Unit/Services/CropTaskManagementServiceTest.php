<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CropTaskManagementService;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\TaskSchedule;
use App\Models\CropStage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CropTaskManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CropTaskManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Resolve service from container to get proper dependencies
        $this->service = $this->app->make(CropTaskManagementService::class);
        
        // Seed CropStage data
        $this->seed(\Database\Seeders\Lookup\CropStageSeeder::class);
    }

    /** @test */
    public function it_schedules_all_stage_tasks_for_a_crop(): void
    {
        $recipe = Recipe::factory()->create([
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 7,
            'suspend_water_hours' => 12,
        ]);

        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'current_stage' => 'germination',
            'planting_at' => Carbon::now(),
        ]);

        $this->service->scheduleAllStageTasks($crop);

        // Should create 3 tasks: blackout transition, light transition, harvest transition
        // Plus 1 for suspend watering
        $tasks = TaskSchedule::where('conditions->crop_id', $crop->id)->get();
        $this->assertCount(3, $tasks); // Watering suspension might be combined with harvest
    }

    /** @test */
    public function it_advances_crop_to_next_stage(): void
    {
        $germStage = CropStage::where('code', 'germination')->first();
        $blackoutStage = CropStage::where('code', 'blackout')->first();
        
        $recipe = Recipe::factory()->create();
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'current_stage_id' => $germStage->id,
            'planting_at' => Carbon::now()->subDays(3),
            'germination_at' => Carbon::now()->subDays(3),
        ]);

        $this->service->advanceStage($crop);

        $crop->refresh();
        $this->assertEquals($blackoutStage->id, $crop->current_stage_id);
        $this->assertNotNull($crop->blackout_at);
    }

    /** @test */
    public function it_maintains_batch_integrity_when_advancing_stages(): void
    {
        $germStage = CropStage::where('code', 'germination')->first();
        $recipe = Recipe::factory()->create();
        $plantingTime = Carbon::now()->subDays(3);

        // Create 3 crops in the same batch
        $crops = [];
        for ($i = 1; $i <= 3; $i++) {
            $crops[] = Crop::factory()->create([
                'recipe_id' => $recipe->id,
                'current_stage_id' => $germStage->id,
                'planting_at' => $plantingTime,
                'tray_number' => "BATCH-{$i}",
            ]);
        }

        // Advance one crop
        $this->service->advanceStage($crops[0]);

        // All crops in the batch should be advanced
        foreach ($crops as $crop) {
            $crop->refresh();
            $this->assertEquals('blackout', $crop->currentStage->code);
        }
    }

    /** @test */
    public function it_suspends_watering_for_entire_batch(): void
    {
        $recipe = Recipe::factory()->create();
        $plantingTime = Carbon::now()->subDays(10);

        // Create 2 crops in the same batch
        $crop1 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planting_at' => $plantingTime,
            'tray_number' => 'BATCH-1',
        ]);

        $crop2 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planting_at' => $plantingTime,
            'tray_number' => 'BATCH-2',
        ]);

        $this->service->suspendWatering($crop1);

        // Both crops should have watering suspended
        $crop1->refresh();
        $crop2->refresh();
        $this->assertNotNull($crop1->watering_suspended_at);
        $this->assertNotNull($crop2->watering_suspended_at);
    }

    /** @test */
    public function it_calculates_expected_harvest_date_correctly(): void
    {
        $recipe = Recipe::factory()->create([
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 7,
        ]);

        $plantingDate = Carbon::parse('2024-01-01');
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planting_at' => $plantingDate,
        ]);

        $expectedHarvest = $this->service->calculateExpectedHarvestDate($crop);

        // Should be 12 days after planting (3 + 2 + 7)
        $this->assertEquals(
            $plantingDate->addDays(12)->format('Y-m-d'),
            $expectedHarvest->format('Y-m-d')
        );
    }
}
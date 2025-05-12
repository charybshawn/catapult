<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\CropTaskGenerator;
use App\Models\Recipe;
use App\Models\Crop;
use App\Models\CropTask;
use App\Models\User; // Needed if factories use it
use App\Models\Consumable; // Needed for recipe factory
use App\Models\SeedVariety; // Needed for recipe factory
use Carbon\Carbon;

class CropTaskGeneratorTest extends TestCase
{
    use RefreshDatabase; // Use RefreshDatabase to ensure a clean slate

    protected CropTaskGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new CropTaskGenerator();
        // Ensure necessary related models can be created by factories
        // User::factory()->create(); // Uncomment if needed by other factories
        Consumable::factory()->count(2)->create(['type' => 'seed']);
        Consumable::factory()->count(2)->create(['type' => 'soil']);
        SeedVariety::factory()->create();
    }

    /** @test */
    public function it_generates_all_tasks_for_standard_recipe(): void
    {
        $plantedAt = Carbon::parse('2024-05-01 10:00:00');
        $recipe = Recipe::factory()->create([
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 5,
            'suspend_water_hours' => 12, // Suspend 12 hours before harvest
        ]);
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => $plantedAt,
            'planting_at' => $plantedAt, // Ensure this matches
        ]);

        $this->generator->generateTasksForBatch($crop, $recipe);

        // Assert: 4 tasks were created (germ, blackout, suspend, harvest)
        $this->assertDatabaseCount('crop_tasks', 4);

        // Assert Germination Task
        $germEnd = $plantedAt->copy()->addDays(3);
        $germTask = CropTask::where('crop_id', $crop->id)->where('task_type', 'end_germination')->first();
        $this->assertNotNull($germTask);
        $this->assertEquals($germEnd->toDateTimeString(), $germTask->scheduled_at->toDateTimeString());
        $this->assertEquals(['target_stage' => 'blackout'], $germTask->details);
        $this->assertEquals('pending', $germTask->status);
        $this->assertEquals($recipe->id, $germTask->recipe_id);

        // Assert Blackout Task
        $blackoutEnd = $germEnd->copy()->addDays(2);
        $blackoutTask = CropTask::where('crop_id', $crop->id)->where('task_type', 'end_blackout')->first();
        $this->assertNotNull($blackoutTask);
        $this->assertEquals($blackoutEnd->toDateTimeString(), $blackoutTask->scheduled_at->toDateTimeString());
        $this->assertEquals(['target_stage' => 'light'], $blackoutTask->details);
        $this->assertEquals('pending', $blackoutTask->status);
        
        // Assert Harvest Task
        $harvestTime = $blackoutEnd->copy()->addDays(5);
        $harvestTask = CropTask::where('crop_id', $crop->id)->where('task_type', 'expected_harvest')->first();
        $this->assertNotNull($harvestTask);
        $this->assertEquals($harvestTime->toDateTimeString(), $harvestTask->scheduled_at->toDateTimeString());
        $this->assertNull($harvestTask->details);
        $this->assertEquals('pending', $harvestTask->status);

        // Assert Suspend Watering Task
        $suspendTime = $harvestTime->copy()->subHours(12);
        $suspendTask = CropTask::where('crop_id', $crop->id)->where('task_type', 'suspend_watering')->first();
        $this->assertNotNull($suspendTask);
        $this->assertEquals($suspendTime->toDateTimeString(), $suspendTask->scheduled_at->toDateTimeString());
        $this->assertNull($suspendTask->details);
        $this->assertEquals('pending', $suspendTask->status);
    }

    /** @test */
    public function it_skips_blackout_task_when_blackout_days_is_zero(): void
    {
        $plantedAt = Carbon::parse('2024-05-01 10:00:00');
        $recipe = Recipe::factory()->create([
            'germination_days' => 3,
            'blackout_days' => 0, // No blackout
            'light_days' => 7,
            'suspend_water_hours' => 12,
        ]);
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => $plantedAt,
            'planting_at' => $plantedAt,
        ]);

        $this->generator->generateTasksForBatch($crop, $recipe);

        // Assert: 3 tasks created (germ, suspend, harvest) - NO blackout
        $this->assertDatabaseCount('crop_tasks', 3);
        $this->assertDatabaseMissing('crop_tasks', [
            'crop_id' => $crop->id,
            'task_type' => 'end_blackout',
        ]);
        
        // Check germ task details point directly to light
        $germTask = CropTask::where('crop_id', $crop->id)->where('task_type', 'end_germination')->first();
        $this->assertNotNull($germTask);
        $this->assertEquals(['target_stage' => 'light'], $germTask->details);
    }

    /** @test */
    public function it_skips_suspend_watering_task_when_suspend_hours_is_zero(): void
    {
        $plantedAt = Carbon::parse('2024-05-01 10:00:00');
        $recipe = Recipe::factory()->create([
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 5,
            'suspend_water_hours' => 0, // No suspension
        ]);
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => $plantedAt,
            'planting_at' => $plantedAt,
        ]);

        $this->generator->generateTasksForBatch($crop, $recipe);

        // Assert: 3 tasks created (germ, blackout, harvest) - NO suspend
        $this->assertDatabaseCount('crop_tasks', 3);
        $this->assertDatabaseMissing('crop_tasks', [
            'crop_id' => $crop->id,
            'task_type' => 'suspend_watering',
        ]);
    }

    /** @test */
    public function it_skips_suspend_watering_task_if_calculated_time_is_before_planting(): void
    {
        // Scenario: Very short grow time, large suspend hours value
        $plantedAt = Carbon::parse('2024-05-01 10:00:00');
        $recipe = Recipe::factory()->create([
            'germination_days' => 0.1, // ~2.4 hours
            'blackout_days' => 0,
            'light_days' => 0.1, // ~2.4 hours 
            'suspend_water_hours' => 6, // Suspend 6 hours before harvest
        ]);
         // Total grow time < 5 hours. Suspend time would be before planting time.
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => $plantedAt,
            'planting_at' => $plantedAt,
        ]);

        $this->generator->generateTasksForBatch($crop, $recipe);

        // Assert: 2 tasks created (germ, harvest) - NO suspend or blackout
        $this->assertDatabaseCount('crop_tasks', 2);
        $this->assertDatabaseMissing('crop_tasks', [
            'crop_id' => $crop->id,
            'task_type' => 'suspend_watering',
        ]);
         $this->assertDatabaseMissing('crop_tasks', [
            'crop_id' => $crop->id,
            'task_type' => 'end_blackout',
        ]);
    }
}

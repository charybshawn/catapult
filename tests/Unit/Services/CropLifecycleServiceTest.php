<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CropLifecycleService;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\SeedVariety;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CropLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    private CropLifecycleService $cropLifecycleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cropLifecycleService = new CropLifecycleService();
    }

    public function test_advanceStage_from_germination_to_blackout(): void
    {
        $recipe = Recipe::factory()->create([
            'germination_days' => 2,
            'blackout_days' => 3,
            'light_days' => 7,
        ]);

        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'current_stage' => 'germination',
            'germination_at' => now()->subDays(2),
        ]);

        $this->cropLifecycleService->advanceStage($crop);

        $this->assertEquals('blackout', $crop->current_stage);
        $this->assertNotNull($crop->blackout_at);
    }

    public function test_advanceStage_from_blackout_to_light(): void
    {
        $recipe = Recipe::factory()->create([
            'germination_days' => 2,
            'blackout_days' => 3,
            'light_days' => 7,
        ]);

        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'current_stage' => 'blackout',
            'blackout_at' => now()->subDays(3),
        ]);

        $this->cropLifecycleService->advanceStage($crop);

        $this->assertEquals('light', $crop->current_stage);
        $this->assertNotNull($crop->light_at);
    }

    public function test_advanceStage_from_light_to_harvested(): void
    {
        $recipe = Recipe::factory()->create([
            'germination_days' => 2,
            'blackout_days' => 3,
            'light_days' => 7,
        ]);

        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'current_stage' => 'light',
            'light_at' => now()->subDays(7),
        ]);

        $this->cropLifecycleService->advanceStage($crop);

        $this->assertEquals('harvested', $crop->current_stage);
        $this->assertNotNull($crop->harvested_at);
    }

    public function test_advanceStage_skips_blackout_when_zero_days(): void
    {
        $recipe = Recipe::factory()->create([
            'germination_days' => 2,
            'blackout_days' => 0,
            'light_days' => 7,
        ]);

        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'current_stage' => 'germination',
            'germination_at' => now()->subDays(2),
        ]);

        $this->cropLifecycleService->advanceStage($crop);

        $this->assertEquals('light', $crop->current_stage);
        $this->assertNotNull($crop->light_at);
        $this->assertNull($crop->blackout_at);
    }

    public function test_advanceStage_does_not_advance_beyond_harvested(): void
    {
        $recipe = Recipe::factory()->create();

        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'current_stage' => 'harvested',
            'harvested_at' => now()->subDay(),
        ]);

        $originalStage = $crop->current_stage;
        $originalHarvestedAt = $crop->harvested_at;

        $this->cropLifecycleService->advanceStage($crop);

        $this->assertEquals($originalStage, $crop->current_stage);
        $this->assertEquals($originalHarvestedAt, $crop->harvested_at);
    }

    public function test_resetToStage_clears_future_timestamps(): void
    {
        $recipe = Recipe::factory()->create();

        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'current_stage' => 'harvested',
            'germination_at' => now()->subDays(10),
            'blackout_at' => now()->subDays(8),
            'light_at' => now()->subDays(5),
            'harvested_at' => now()->subDay(),
        ]);

        $this->cropLifecycleService->resetToStage($crop, 'blackout');

        $this->assertEquals('blackout', $crop->current_stage);
        $this->assertNotNull($crop->germination_at);
        $this->assertNotNull($crop->blackout_at);
        $this->assertNull($crop->light_at);
        $this->assertNull($crop->harvested_at);
    }

    public function test_calculateExpectedHarvestDate(): void
    {
        $recipe = Recipe::factory()->create([
            'days_to_maturity' => 12,
        ]);

        $plantedAt = Carbon::parse('2023-01-01 08:00:00');
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => $plantedAt,
        ]);

        $result = $this->cropLifecycleService->calculateExpectedHarvestDate($crop);

        $expected = $plantedAt->copy()->addDays(12);
        $this->assertEquals($expected->toDateString(), $result->toDateString());
    }

    public function test_calculateExpectedHarvestDate_returns_null_without_recipe(): void
    {
        $crop = Crop::factory()->create([
            'recipe_id' => null,
        ]);

        $result = $this->cropLifecycleService->calculateExpectedHarvestDate($crop);

        $this->assertNull($result);
    }

    public function test_suspendWatering_sets_timestamp(): void
    {
        $crop = Crop::factory()->create([
            'watering_suspended_at' => null,
        ]);

        $this->cropLifecycleService->suspendWatering($crop);

        $this->assertNotNull($crop->watering_suspended_at);
    }

    public function test_resumeWatering_clears_timestamp(): void
    {
        $crop = Crop::factory()->create([
            'watering_suspended_at' => now(),
        ]);

        $this->cropLifecycleService->resumeWatering($crop);

        $this->assertNull($crop->watering_suspended_at);
    }

    public function test_calculateDaysInCurrentStage(): void
    {
        $baseDate = Carbon::parse('2023-01-01 08:00:00');
        Carbon::setTestNow($baseDate->copy()->addDays(5));

        $crop = Crop::factory()->create([
            'current_stage' => 'light',
            'light_at' => $baseDate,
        ]);

        $result = $this->cropLifecycleService->calculateDaysInCurrentStage($crop);

        $this->assertEquals(5, $result);

        Carbon::setTestNow(); // Reset
    }

    public function test_calculateDaysInCurrentStage_returns_zero_without_stage_timestamp(): void
    {
        $crop = Crop::factory()->create([
            'current_stage' => 'light',
            'light_at' => null,
        ]);

        $result = $this->cropLifecycleService->calculateDaysInCurrentStage($crop);

        $this->assertEquals(0, $result);
    }
}
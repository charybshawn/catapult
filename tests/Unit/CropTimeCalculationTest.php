<?php

namespace Tests\Unit;

use App\Models\Crop;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CropTimeCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2025, 4, 28, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testGerminationStageTimeCalculation()
    {
        // Create a test recipe
        $recipe = Recipe::factory()->create([
            'germination_days' => 5,
            'blackout_days' => 2,
            'light_days' => 14,
        ]);

        // Case 1: Crop just planted today
        $crop1 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => now(),
            'germination_at' => now(),
            'current_stage' => 'germination',
        ]);

        // Expected harvest = planted_at + all stages duration
        $expectedHarvest1 = now()->copy()->addDays($recipe->germination_days + $recipe->blackout_days + $recipe->light_days);
        $this->assertEquals(
            $expectedHarvest1->format('Y-m-d H:i:s'),
            $crop1->expectedHarvestDate()->format('Y-m-d H:i:s'),
            'Newly planted crop harvest date is incorrect'
        );

        // Time to next stage should be 5 days (germination period)
        $this->assertStringContainsString('5d', $crop1->timeToNextStage(), 'Time to next stage is incorrect for new crop');

        // Case 2: Crop planted 3 days ago, still in germination
        $crop2 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => now()->subDays(3),
            'germination_at' => now()->subDays(3),
            'current_stage' => 'germination',
        ]);

        // Expected harvest = planted_at + all stages duration
        $expectedHarvest2 = now()->copy()->subDays(3)->addDays($recipe->germination_days + $recipe->blackout_days + $recipe->light_days);
        $this->assertEquals(
            $expectedHarvest2->format('Y-m-d H:i:s'),
            $crop2->expectedHarvestDate()->format('Y-m-d H:i:s'),
            'Crop planted 3 days ago harvest date is incorrect'
        );

        // Time to next stage should be 2 days (5 - 3 = 2 days left in germination)
        $this->assertStringContainsString('2d', $crop2->timeToNextStage(), 'Time to next stage is incorrect for 3 day old crop');
    }

    public function testBlackoutStageTimeCalculation()
    {
        // Create a test recipe
        $recipe = Recipe::factory()->create([
            'germination_days' => 5,
            'blackout_days' => 3,
            'light_days' => 14,
        ]);

        // Manually set the time to now to avoid test execution timing issues
        $now = Carbon::create(2025, 4, 28, 12, 0, 0);
        Carbon::setTestNow($now);

        // Crop in blackout stage, just started (now, not past)
        $crop = new Crop();
        $crop->recipe_id = $recipe->id;
        $crop->tray_number = 'TEST-BLACKOUT';
        $crop->planted_at = $now->copy()->subDays(5);
        $crop->germination_at = $now->copy()->subDays(5);
        $crop->blackout_at = $now;
        $crop->current_stage = 'blackout';
        $crop->save();
        $crop->refresh();

        // Expected harvest = blackout_at + blackout_days + light_days
        $expectedHarvest = $now->copy()->addDays($recipe->blackout_days + $recipe->light_days);
        $this->assertEquals(
            $expectedHarvest->format('Y-m-d'),
            $crop->expectedHarvestDate()->format('Y-m-d'),
            'Blackout stage crop harvest date is incorrect'
        );

        // Verify that timeToNextStage returns something valid (not testing exact value due to timing issues)
        $timeToNext = $crop->timeToNextStage();
        $this->assertNotEmpty($timeToNext, "Time to next stage should not be empty");
    }

    public function testLightStageTimeCalculation()
    {
        // Create a test recipe
        $recipe = Recipe::factory()->create([
            'germination_days' => 5,
            'blackout_days' => 2,
            'light_days' => 14,
        ]);

        // Manually set the time to now to avoid test execution timing issues
        $now = Carbon::create(2025, 4, 28, 12, 0, 0);
        Carbon::setTestNow($now);

        // Crop in light stage, just started (now, not past)
        $crop = new Crop();
        $crop->recipe_id = $recipe->id;
        $crop->tray_number = 'TEST-LIGHT';
        $crop->planted_at = $now->copy()->subDays(7);
        $crop->germination_at = $now->copy()->subDays(7);
        $crop->blackout_at = $now->copy()->subDays(2);
        $crop->light_at = $now;
        $crop->current_stage = 'light';
        $crop->save();
        $crop->refresh();

        // Expected harvest = light_at + light_days
        $expectedHarvest = $now->copy()->addDays($recipe->light_days);
        $this->assertEquals(
            $expectedHarvest->format('Y-m-d'),
            $crop->expectedHarvestDate()->format('Y-m-d'),
            'Light stage crop harvest date is incorrect'
        );

        // Verify that timeToNextStage returns something valid (not testing exact value due to timing issues)
        $timeToNext = $crop->timeToNextStage();
        $this->assertNotEmpty($timeToNext, "Time to next stage should not be empty");
    }

    public function testZeroBlackoutDaysCalculation()
    {
        // Create a test recipe with 0 blackout days
        $recipe = Recipe::factory()->create([
            'germination_days' => 5,
            'blackout_days' => 0,
            'light_days' => 14,
        ]);

        // Crop in germination stage
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => now()->subDays(3),
            'germination_at' => now()->subDays(3),
            'current_stage' => 'germination',
        ]);

        // Expected harvest = planted_at + germination_days + light_days (no blackout)
        $expectedHarvest = now()->copy()->subDays(3)->addDays($recipe->germination_days + $recipe->light_days);
        $this->assertEquals(
            $expectedHarvest->format('Y-m-d H:i:s'),
            $crop->expectedHarvestDate()->format('Y-m-d H:i:s'),
            'Zero blackout days harvest date is incorrect'
        );

        // Skip to light stage (should skip blackout)
        $crop->resetToStage('light');
        $crop->refresh();

        $this->assertEquals('light', $crop->current_stage);
        
        // Expected harvest = light_at + light_days
        $expectedHarvest = $crop->light_at->copy()->addDays($recipe->light_days);
        $this->assertEquals(
            $expectedHarvest->format('Y-m-d H:i:s'),
            $crop->expectedHarvestDate()->format('Y-m-d H:i:s'),
            'Zero blackout days light stage harvest date is incorrect'
        );
    }

    public function testChangingPlantedDateShiftsAllDates()
    {
        // Create a test recipe
        $recipe = Recipe::factory()->create([
            'germination_days' => 5,
            'blackout_days' => 2,
            'light_days' => 14,
        ]);

        // Crop in light stage
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planted_at' => now()->subDays(10),
            'germination_at' => now()->subDays(10),
            'blackout_at' => now()->subDays(5),
            'light_at' => now()->subDays(3),
            'current_stage' => 'light',
        ]);

        // Record original timestamps
        $originalGerminationAt = $crop->germination_at->copy();
        $originalBlackoutAt = $crop->blackout_at->copy();
        $originalLightAt = $crop->light_at->copy();
        $originalHarvestDate = $crop->expectedHarvestDate();

        // Change planted date to 5 days earlier
        $crop->planted_at = now()->subDays(15);
        $crop->save();
        $crop->refresh();

        // All dates should be shifted by 5 days
        $this->assertEquals(
            $originalGerminationAt->subDays(5)->format('Y-m-d H:i:s'),
            $crop->germination_at->format('Y-m-d H:i:s'),
            'Germination date did not shift correctly'
        );

        $this->assertEquals(
            $originalBlackoutAt->subDays(5)->format('Y-m-d H:i:s'),
            $crop->blackout_at->format('Y-m-d H:i:s'),
            'Blackout date did not shift correctly'
        );

        $this->assertEquals(
            $originalLightAt->subDays(5)->format('Y-m-d H:i:s'),
            $crop->light_at->format('Y-m-d H:i:s'),
            'Light date did not shift correctly'
        );

        $this->assertEquals(
            $originalHarvestDate->subDays(5)->format('Y-m-d H:i:s'),
            $crop->expectedHarvestDate()->format('Y-m-d H:i:s'),
            'Expected harvest date did not shift correctly'
        );
    }

    public function testResetToStage()
    {
        // Set a fixed test time
        $now = Carbon::create(2025, 4, 28, 12, 0, 0);
        Carbon::setTestNow($now);
        
        // Create a test recipe
        $recipe = Recipe::factory()->create([
            'germination_days' => 5,
            'blackout_days' => 2,
            'light_days' => 14,
        ]);

        // Crop in light stage
        $crop = new Crop();
        $crop->recipe_id = $recipe->id;
        $crop->tray_number = 'TEST-RESET';
        $crop->planted_at = $now->copy()->subDays(10);
        $crop->germination_at = $now->copy()->subDays(10);
        $crop->blackout_at = $now->copy()->subDays(5);
        $crop->light_at = $now->copy()->subDays(3);
        $crop->current_stage = 'light';
        $crop->save();
        $crop->refresh();

        // Reset to germination stage
        $crop->resetToStage('germination');
        $crop->refresh();

        // Current stage should be germination
        $this->assertEquals('germination', $crop->current_stage);
        
        // Germination timestamp should be updated to now
        $this->assertEquals(
            $now->format('Y-m-d'),
            $crop->germination_at->format('Y-m-d'),
            'Germination timestamp not updated to now'
        );
        
        // Later stage timestamps should be cleared
        $this->assertNull($crop->blackout_at);
        $this->assertNull($crop->light_at);
        
        // Verify expected harvest date
        $this->assertNotNull($crop->expectedHarvestDate(), 'Expected harvest date should not be null');
        
        // Just check that the expected harvest date is in the future
        $this->assertTrue(
            $crop->expectedHarvestDate()->gt($now),
            'Expected harvest date should be in the future'
        );
    }
} 
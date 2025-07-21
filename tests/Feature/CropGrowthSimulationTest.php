<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * This test simulates the growth of a crop throughout its entire lifecycle
 * by manipulating Carbon's test time to synthesize the passage of time.
 */
class CropGrowthSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected Recipe $recipe;

    protected Crop $crop;

    protected Carbon $startDate;

    protected array $timePoints = [];

    protected array $stageResults = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Start with a fixed date and time
        $this->startDate = Carbon::create(2025, 5, 1, 9, 0, 0); // May 1, 2025 at 9:00 AM
        Carbon::setTestNow($this->startDate);

        echo "\n=== CROP GROWTH SIMULATION ===\n";
        echo 'Starting simulation at: '.$this->startDate->format('Y-m-d H:i:s')."\n\n";

        // Create a standard recipe for microgreens
        $this->recipe = Recipe::factory()->create([
            'name' => 'Test Sunflower',
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 7,
        ]);

        echo "Recipe: {$this->recipe->name}\n";
        echo "- Germination: {$this->recipe->germination_days} days\n";
        echo "- Blackout: {$this->recipe->blackout_days} days\n";
        echo "- Light: {$this->recipe->light_days} days\n";
        echo '- Total grow time: '.
             ($this->recipe->germination_days + $this->recipe->blackout_days + $this->recipe->light_days).
             " days\n\n";

        // Create a crop at the initial planting stage
        $this->crop = Crop::factory()->create([
            'recipe_id' => $this->recipe->id,
            'tray_number' => 'SIM-001',
            'planting_at' => $this->startDate,
            'germination_at' => $this->startDate->copy()->addMinute(), // Germination must be after planting
            'current_stage' => 'germination',
        ]);

        echo "Created crop: Tray #{$this->crop->tray_number}\n";
        echo "Initial stage: {$this->crop->current_stage}\n";
        echo 'Expected harvest date: '.$this->crop->expectedHarvestDate()->format('Y-m-d H:i:s')."\n\n";

        // Calculate and store the timestamps for each stage transition
        $this->calculateTimePoints();
    }

    protected function tearDown(): void
    {
        // Reset the Carbon test time
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Calculate the exact time points when stage transitions should occur.
     */
    protected function calculateTimePoints(): void
    {
        $germEnd = $this->startDate->copy()->addDays($this->recipe->germination_days);
        $blackoutEnd = $germEnd->copy()->addDays($this->recipe->blackout_days);
        $lightEnd = $blackoutEnd->copy()->addDays($this->recipe->light_days);

        $this->timePoints = [
            'start' => $this->startDate->copy(),
            'germination_end' => $germEnd,
            'blackout_end' => $blackoutEnd,
            'harvest' => $lightEnd,
        ];

        echo "Time Points Calculated:\n";
        echo '- Start: '.$this->timePoints['start']->format('Y-m-d H:i:s')."\n";
        echo '- End of Germination: '.$this->timePoints['germination_end']->format('Y-m-d H:i:s')."\n";
        echo '- End of Blackout: '.$this->timePoints['blackout_end']->format('Y-m-d H:i:s')."\n";
        echo '- Harvest Time: '.$this->timePoints['harvest']->format('Y-m-d H:i:s')."\n\n";
    }

    /**
     * Capture the current state of the crop for reporting.
     */
    protected function captureState(string $timePoint): void
    {
        $this->stageResults[$timePoint] = [
            'current_time' => now()->format('Y-m-d H:i:s'),
            'current_stage' => $this->crop->current_stage,
            'time_since_planting' => $this->formatDiff($this->crop->germinated_at, now()),
            'time_in_stage' => $this->formatDiff($this->crop->{$this->crop->current_stage.'_at'}, now()),
            'time_to_next_stage' => $this->crop->timeToNextStage(),
            'expected_harvest' => $this->crop->expectedHarvestDate() ? $this->crop->expectedHarvestDate()->format('Y-m-d H:i:s') : 'N/A',
            'time_to_harvest' => $this->crop->expectedHarvestDate() ? $this->formatDiff(now(), $this->crop->expectedHarvestDate()) : 'N/A',
        ];
    }

    /**
     * Format a time difference into a human-readable string.
     */
    protected function formatDiff(Carbon $from, Carbon $to): string
    {
        $diff = $from->diff($to);

        return "{$diff->days}d {$diff->h}h {$diff->i}m";
    }

    /**
     * Print a detailed report about the current state of the crop.
     */
    protected function printCurrentState(string $timePoint): void
    {
        $this->crop->refresh();
        $this->captureState($timePoint);
        $state = $this->stageResults[$timePoint];

        echo "=== {$timePoint} ===\n";
        echo "Current time: {$state['current_time']}\n";
        echo "Current stage: {$state['current_stage']}\n";
        echo "Time since planting: {$state['time_since_planting']}\n";
        echo "Time in current stage: {$state['time_in_stage']}\n";
        echo "Time to next stage: {$state['time_to_next_stage']}\n";
        echo "Expected harvest: {$state['expected_harvest']}\n";
        echo "Time to harvest: {$state['time_to_harvest']}\n\n";
    }

    /**
     * Test the simulation of a crop's growth through its entire lifecycle.
     */
    public function test_crop_growth_lifecycle(): void
    {
        // Step 1: Start Day (already set up)
        $this->printCurrentState('Day 1 - Initial Planting');

        // Step 2: Middle of Germination
        Carbon::setTestNow($this->startDate->copy()->addHours(36)); // 1.5 days later
        $this->printCurrentState('Day 2 - Mid Germination');

        // Step 3: End of Germination / Start of Blackout
        Carbon::setTestNow($this->timePoints['germination_end']);
        $this->printCurrentState('Day 3 - End of Germination');

        // Step 4: Transition to Blackout
        $this->crop->advanceStage();
        $this->crop->refresh();
        $this->printCurrentState('Day 3 - Start of Blackout');

        // Step 5: Middle of Blackout
        Carbon::setTestNow($this->timePoints['germination_end']->copy()->addHours(24)); // 1 day into blackout
        $this->printCurrentState('Day 4 - Mid Blackout');

        // Step 6: End of Blackout / Start of Light
        Carbon::setTestNow($this->timePoints['blackout_end']);
        $this->printCurrentState('Day 5 - End of Blackout');

        // Step 7: Transition to Light
        $this->crop->advanceStage();
        $this->crop->refresh();
        $this->printCurrentState('Day 5 - Start of Light');

        // Step 8: Middle of Light
        Carbon::setTestNow($this->timePoints['blackout_end']->copy()->addDays(3)); // 3 days into light
        $this->printCurrentState('Day 8 - Mid Light');

        // Step 9: Almost Harvest Time
        Carbon::setTestNow($this->timePoints['harvest']->copy()->subHours(12)); // 12 hours before harvest
        $this->printCurrentState('Day 12 - Almost Harvest');

        // Step 10: Harvest Time
        Carbon::setTestNow($this->timePoints['harvest']);
        $this->printCurrentState('Day 12 - Harvest Time');

        // Step 11: Perform the Harvest
        $this->crop->advanceStage();
        $this->crop->harvest_weight_grams = 150;
        $this->crop->save();
        $this->crop->refresh();
        $this->printCurrentState('Day 12 - After Harvest');

        // Final assertions to verify the simulation worked correctly
        $this->assertEquals('harvested', $this->crop->current_stage, 'Crop should be in harvested stage at the end');
        $this->assertNotNull($this->crop->harvested_at, 'Harvested_at timestamp should be set');
        $this->assertEquals(150, $this->crop->harvest_weight_grams, 'Harvest weight should be recorded');

        // Print summary of key timepoints
        echo "\n=== SIMULATION SUMMARY ===\n";
        foreach ($this->stageResults as $timePoint => $state) {
            echo "- {$timePoint}: {$state['current_stage']} stage at {$state['current_time']}\n";
        }
        echo "\nSimulation completed successfully!\n";
    }

    /**
     * Test simulating time with daily snapshots.
     */
    public function test_daily_snapshots(): void
    {
        echo "\n=== DAILY SNAPSHOTS ===\n";

        // Loop through each day of the grow cycle and take measurements
        $totalDays = $this->recipe->germination_days + $this->recipe->blackout_days + $this->recipe->light_days;

        for ($day = 0; $day <= $totalDays; $day++) {
            // Set the time to the start date plus the number of days
            Carbon::setTestNow($this->startDate->copy()->addDays($day));

            // Check if we need to advance the stage
            if ($day == $this->recipe->germination_days) {
                $this->crop->advanceStage(); // Move to blackout
                $this->crop->refresh();
            } elseif ($day == ($this->recipe->germination_days + $this->recipe->blackout_days)) {
                $this->crop->advanceStage(); // Move to light
                $this->crop->refresh();
            } elseif ($day == $totalDays) {
                $this->crop->advanceStage(); // Move to harvested
                $this->crop->harvest_weight_grams = 150;
                $this->crop->save();
                $this->crop->refresh();
            }

            // Capture and print the state
            echo 'Day '.($day + 1).' - '.now()->format('Y-m-d').' - Stage: '.$this->crop->current_stage."\n";
            echo '  Time to next stage: '.$this->crop->timeToNextStage()."\n";

            if ($this->crop->current_stage != 'harvested') {
                echo '  Expected harvest: '.$this->crop->expectedHarvestDate()->format('Y-m-d')."\n";
                echo '  Time to harvest: '.$this->formatDiff(now(), $this->crop->expectedHarvestDate())."\n";
            }
            echo "\n";
        }

        $this->assertEquals('harvested', $this->crop->current_stage, 'Crop should be in harvested stage at the end');
    }
}

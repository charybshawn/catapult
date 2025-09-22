<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\CropBatch;
use App\Models\CropStage;
use App\Models\Recipe;
use App\Services\CropStageTransitionService;
use App\Services\CropStageTimelineService;
use App\Services\CropTimeCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Comprehensive test that simulates the complete lifecycle of a crop
 * from creation through all stage transitions to deletion.
 * This reflects the actual user workflow and verifies timestamps,
 * statuses, timelines, and calculations are working correctly.
 * 
 * THIS TEST SHOULD EXPOSE BUGS - DO NOT MODIFY TO ACCOMMODATE THEM!
 */
class CropLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected Recipe $recipe;
    protected Crop $crop;
    protected CropBatch $batch;
    protected Carbon $plantingTime;
    protected CropStageTransitionService $transitionService;
    protected CropStageTimelineService $timelineService;
    protected CropTimeCalculator $timeCalculator;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set a fixed test time
        $this->plantingTime = Carbon::parse('2025-07-21 08:00:00', config('app.timezone'));
        Carbon::setTestNow($this->plantingTime);
        
        // Initialize services
        $this->transitionService = app(CropStageTransitionService::class);
        $this->timelineService = app(CropStageTimelineService::class);
        $this->timeCalculator = app(CropTimeCalculator::class);
        
        // Seed crop stages
        $this->seedCropStages();
        
        // Create test recipe with realistic timing
        $this->recipe = Recipe::factory()->create([
            'name' => 'Test Microgreens',
            'germination_days' => 3,
            'blackout_days' => 2,
            'light_days' => 7,
            'seed_soak_hours' => 0, // Explicitly no soaking required
        ]);
        
        echo "\n=== CROP LIFECYCLE TEST ===\n";
        echo "Test Recipe: {$this->recipe->name}\n";
        echo "Planting Time: {$this->plantingTime->format('Y-m-d H:i:s')}\n\n";
    }

    protected function seedCropStages(): void
    {
        $stages = [
            ['code' => 'soaking', 'name' => 'Soaking', 'color' => 'info', 'sort_order' => 0],
            ['code' => 'germination', 'name' => 'Germination', 'color' => 'warning', 'sort_order' => 1],
            ['code' => 'blackout', 'name' => 'Blackout', 'color' => 'secondary', 'sort_order' => 2],
            ['code' => 'light', 'name' => 'Light', 'color' => 'info', 'sort_order' => 3],
            ['code' => 'harvested', 'name' => 'Harvested', 'color' => 'success', 'sort_order' => 4],
        ];

        foreach ($stages as $stage) {
            CropStage::updateOrCreate(
                ['code' => $stage['code']],
                $stage
            );
        }
    }

    /** @test */
    public function test_complete_crop_lifecycle()
    {
        // Step 1: Create crop batch (user starts new batch)
        $this->createCropBatch();
        
        // Step 2: Create crop (user plants seeds)
        $this->createCrop();
        
        // Step 3: Verify initial state
        $this->verifyInitialCropState();
        
        // Step 4: Test stage transitions
        $this->testStageTransitions();
        
        // Step 5: Test crop updates
        $this->testCropUpdates();
        
        // Step 6: Test timeline and calculations throughout
        $this->testTimelineAndCalculations();
        
        // Step 7: Complete harvest
        $this->completeHarvest();
        
        // Step 8: Test deletion/cleanup
        $this->testCropDeletion();
    }

    protected function createCropBatch(): void
    {
        echo "1. Creating crop batch...\n";
        
        $this->batch = CropBatch::create([
            'recipe_id' => $this->recipe->id,
        ]);
        
        $this->assertNotNull($this->batch);
        $this->assertEquals($this->recipe->id, $this->batch->recipe_id);
        
        echo "   ✓ Batch created with ID: {$this->batch->id}\n";
    }

    protected function createCrop(): void
    {
        echo "2. Creating crop...\n";
        
        // Get the germination stage
        $germinationStage = CropStage::where('code', 'germination')->first();
        
        $cropData = [
            'recipe_id' => $this->recipe->id,
            'crop_batch_id' => $this->batch->id,
            'current_stage_id' => $germinationStage->id,
            'tray_number' => 'TEST-001',
            'tray_count' => 1,
            'planting_at' => $this->plantingTime,
            'time_to_next_stage_minutes' => 0,
            'time_to_next_stage_status' => 'Calculating...',
            'stage_age_minutes' => 0,
            'stage_age_status' => '0m',
            'total_age_minutes' => 0,
            'total_age_status' => '0m',
        ];
        
        $this->crop = Crop::create($cropData);
        
        $this->assertNotNull($this->crop);
        $this->assertEquals($this->recipe->id, $this->crop->recipe_id);
        $this->assertEquals($this->batch->id, $this->crop->crop_batch_id);
        $this->assertEquals('TEST-001', $this->crop->tray_number);
        
        echo "   ✓ Crop created with ID: {$this->crop->id}\n";
        echo "   ✓ Tray Number: {$this->crop->tray_number}\n";
    }

    protected function verifyInitialCropState(): void
    {
        echo "3. Verifying initial crop state...\n";
        
        // Load relationships
        $this->crop->load('currentStage', 'recipe', 'batch');
        
        
        // BUG EXPOSED: This should work but fails due to accessor conflict
        // The currentStage relationship should return a CropStage model, not a string
        $this->assertInstanceOf(CropStage::class, $this->crop->currentStage, 'currentStage should return CropStage model, not string from accessor');
        $this->assertEquals('germination', $this->crop->currentStage->code);
        
        // Planting time should match what we set (allowing for reasonable timezone handling)
        // Allow for up to 1 hour difference due to DST or timezone handling
        $timeDiff = abs($this->plantingTime->diffInMinutes($this->crop->planting_at));
        $this->assertLessThanOrEqual(60, $timeDiff, "Planting time difference should be at most 1 hour, got {$timeDiff} minutes");
        
        // Initially, stage-specific timestamps should be null
        $this->assertNull($this->crop->germination_at);
        $this->assertNull($this->crop->blackout_at);
        $this->assertNull($this->crop->light_at);
        $this->assertNull($this->crop->harvested_at);
        
        echo "   ✓ Current stage: {$this->crop->currentStage->code}\n";
        echo "   ✓ Planting time set correctly\n";
        echo "   ✓ Stage timestamps initially null\n";
        
        // BUG EXPOSED: This should work but fails due to accessor conflict in service
        // The transition service should be able to initialize crop stages
        $this->transitionService->initializeCropStage($this->crop, $this->plantingTime);
        $this->crop->refresh();
        
        // Now germination_at should be set
        $this->assertNotNull($this->crop->germination_at, 'CropStageTransitionService should set germination_at');
        $this->assertEquals($this->plantingTime->format('Y-m-d H:i'), $this->crop->germination_at->format('Y-m-d H:i'));
        
        echo "   ✓ Stage initialized - germination_at set\n";
    }

    protected function testStageTransitions(): void
    {
        echo "4. Testing stage transitions...\n";
        
        // Advance time and test germination -> blackout transition
        Carbon::setTestNow($this->plantingTime->copy()->addDays(3)->addHours(2));
        
        $blackoutStage = CropStage::where('code', 'blackout')->first();
        
        // Transition to blackout
        $this->crop->update([
            'current_stage_id' => $blackoutStage->id,
            'blackout_at' => Carbon::now(),
        ]);
        
        $this->crop->refresh();
        $this->crop->load('currentStage');
        
        // BUG EXPOSED: currentStage should return the model, not string
        $this->assertInstanceOf(CropStage::class, $this->crop->currentStage, 'currentStage relationship should work after stage transition');
        $this->assertEquals('blackout', $this->crop->currentStage->code);
        $this->assertNotNull($this->crop->blackout_at);
        
        echo "   ✓ Transitioned to blackout stage\n";
        echo "   ✓ Blackout timestamp set: {$this->crop->blackout_at}\n";
        
        // Advance time and test blackout -> light transition
        Carbon::setTestNow($this->plantingTime->copy()->addDays(5)->addHours(4));
        
        $lightStage = CropStage::where('code', 'light')->first();
        
        // Transition to light
        $this->crop->update([
            'current_stage_id' => $lightStage->id,
            'light_at' => Carbon::now(),
        ]);
        
        $this->crop->refresh();
        $this->crop->load('currentStage');
        
        $this->assertInstanceOf(CropStage::class, $this->crop->currentStage);
        $this->assertEquals('light', $this->crop->currentStage->code);
        $this->assertNotNull($this->crop->light_at);
        
        echo "   ✓ Transitioned to light stage\n";
        echo "   ✓ Light timestamp set: {$this->crop->light_at}\n";
    }

    protected function testCropUpdates(): void
    {
        echo "5. Testing crop updates...\n";
        
        // Test updating tray information
        echo "   Current tray count: {$this->crop->tray_count}\n";
        
        $this->crop->update([
            'tray_number' => 'TEST-001-UPDATED',
            'tray_count' => 3, // User wants 3 trays
        ]);
        
        $this->crop->refresh();
        
        $this->assertEquals('TEST-001-UPDATED', $this->crop->tray_number);
        
        // BUG EXPOSED: CropObserver forces tray_count to 1, ignoring user input
        // Users should be able to set custom tray counts
        $this->assertEquals(3, $this->crop->tray_count, 'Users should be able to set custom tray counts - CropObserver should not override this');
        
        echo "   ✓ Tray number updated to: {$this->crop->tray_number}\n";
        echo "   ✓ Tray count should be: 3, actual: {$this->crop->tray_count}\n";
        
        // Test updating timing information
        $originalPlantingTime = $this->crop->planting_at;
        $newPlantingTime = $originalPlantingTime->copy()->subHours(2);
        
        $this->crop->update(['planting_at' => $newPlantingTime]);
        $this->crop->refresh();
        
        $this->assertEquals($newPlantingTime->format('Y-m-d H:i'), $this->crop->planting_at->format('Y-m-d H:i'));
        echo "   ✓ Planting time adjusted\n";
    }

    protected function testTimelineAndCalculations(): void
    {
        echo "6. Testing timeline and calculations...\n";
        
        // Test timeline generation
        $timeline = $this->timelineService->generateTimeline($this->crop);
        
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('germination', $timeline);
        $this->assertArrayHasKey('blackout', $timeline);
        $this->assertArrayHasKey('light', $timeline);
        
        echo "   ✓ Timeline generated with all stages\n";
        
        // Debug what timeline actually shows
        echo "   Timeline statuses:\n";
        foreach ($timeline as $stageCode => $stage) {
            echo "     {$stageCode}: {$stage['status']}\n";
        }
        
        // BUG EXPOSED: Timeline should show realistic statuses based on actual stage progression
        // We've set timestamps for germination, blackout, and light stages
        // So timeline should reflect completed/current statuses, not all "future"
        $this->assertEquals('completed', $timeline['germination']['status'], 'Germination should be completed since we have germination_at timestamp');
        $this->assertEquals('completed', $timeline['blackout']['status'], 'Blackout should be completed since we have blackout_at timestamp');
        $this->assertContains($timeline['light']['status'], ['current', 'completed'], 'Light should be current or completed since we have light_at timestamp');
        
        echo "   ✓ Timeline statuses should reflect actual stage progression\n";
        
        // Test time calculations
        $totalAge = $this->crop->planting_at->diffInMinutes(Carbon::now());
        $this->assertGreaterThan(0, $totalAge);
        
        echo "   ✓ Time calculations working\n";
        echo "   ✓ Current total age: " . round($totalAge / 60, 1) . " hours\n";
    }

    protected function completeHarvest(): void
    {
        echo "7. Completing harvest...\n";
        
        // Advance time to harvest
        Carbon::setTestNow($this->plantingTime->copy()->addDays(12));
        
        $harvestedStage = CropStage::where('code', 'harvested')->first();
        
        // Complete harvest
        $this->crop->update([
            'current_stage_id' => $harvestedStage->id,
            'harvested_at' => Carbon::now(),
        ]);
        
        $this->crop->refresh();
        $this->crop->load('currentStage');
        
        $this->assertInstanceOf(CropStage::class, $this->crop->currentStage);
        $this->assertEquals('harvested', $this->crop->currentStage->code);
        $this->assertNotNull($this->crop->harvested_at);
        
        echo "   ✓ Crop harvested\n";
        echo "   ✓ Harvest timestamp: {$this->crop->harvested_at}\n";
        
        // Verify full lifecycle timestamps
        $this->assertNotNull($this->crop->planting_at);
        $this->assertNotNull($this->crop->germination_at);
        $this->assertNotNull($this->crop->blackout_at);
        $this->assertNotNull($this->crop->light_at);
        $this->assertNotNull($this->crop->harvested_at);
        
        echo "   ✓ All lifecycle timestamps present\n";
    }

    protected function testCropDeletion(): void
    {
        echo "8. Testing crop deletion...\n";
        
        $cropId = $this->crop->id;
        $batchId = $this->batch->id;
        
        // Verify crop exists
        $this->assertDatabaseHas('crops', ['id' => $cropId]);
        $this->assertDatabaseHas('crop_batches', ['id' => $batchId]);
        
        // Delete crop
        $this->crop->delete();
        
        // Verify crop is deleted
        $this->assertDatabaseMissing('crops', ['id' => $cropId]);
        echo "   ✓ Crop deleted successfully\n";
        
        // Clean up batch
        $this->batch->delete();
        $this->assertDatabaseMissing('crop_batches', ['id' => $batchId]);
        echo "   ✓ Batch cleaned up\n";
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        echo "\n=== TEST COMPLETED - BUGS SHOULD BE EXPOSED ===\n";
        parent::tearDown();
    }
}
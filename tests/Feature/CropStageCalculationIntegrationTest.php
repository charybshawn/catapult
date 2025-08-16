<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Crop;
use App\Models\CropBatch;
use App\Models\CropStage;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CropStageCalculationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed crop stages
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\Lookup\\CropStageSeeder']);
    }

    public function test_crop_stage_updates_automatically_when_timestamps_change()
    {
        // Create a recipe and crop
        $recipe = Recipe::factory()->create();
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'germination_at' => Carbon::now(),
            'current_stage_id' => null, // Will be set automatically
        ]);

        // Verify the stage was set automatically to germination
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('germination', $crop->getRelation('currentStage')->code);

        // Add blackout timestamp
        $crop->blackout_at = Carbon::now()->addDays(2);
        $crop->save();

        // Verify the stage updated automatically to blackout
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('blackout', $crop->getRelation('currentStage')->code);

        // Add light timestamp
        $crop->light_at = Carbon::now()->addDays(5);
        $crop->save();

        // Verify the stage updated automatically to light
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('light', $crop->getRelation('currentStage')->code);

        // Add harvested timestamp
        $crop->harvested_at = Carbon::now()->addDays(12);
        $crop->save();

        // Verify the stage updated automatically to harvested
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('harvested', $crop->getRelation('currentStage')->code);
    }

    public function test_crop_batch_reflects_crop_stage_changes()
    {
        // Create a recipe and crop batch with crops
        $recipe = Recipe::factory()->create();
        $cropBatch = CropBatch::factory()->create([
            'recipe_id' => $recipe->id,
        ]);

        $crop1 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'crop_batch_id' => $cropBatch->id,
            'germination_at' => Carbon::now(),
            'tray_number' => '1',
        ]);

        $crop2 = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'crop_batch_id' => $cropBatch->id,
            'germination_at' => Carbon::now(),
            'tray_number' => '2',
        ]);

        // Load the crop batch with crops
        $cropBatch->load('crops.currentStage');

        // Verify the batch shows germination stage from first crop
        $this->assertEquals('germination', CropStage::find($cropBatch->current_stage_id)->code);

        // Advance the first crop to blackout
        $crop1->blackout_at = Carbon::now()->addDays(2);
        $crop1->save();

        // Invalidate batch cache and check again
        $cropBatch->invalidateFirstCropCache();
        $cropBatch->load('crops.currentStage');

        // Verify the batch now shows blackout stage
        $this->assertEquals('blackout', CropStage::find($cropBatch->current_stage_id)->code);
    }

    public function test_soaking_workflow()
    {
        // Create a recipe that requires soaking
        $recipe = Recipe::factory()->create([
            'seed_soak_hours' => 12,
        ]);

        // Create a crop that requires soaking
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'requires_soaking' => true,
            'soaking_at' => Carbon::now(),
            'germination_at' => null, // Only soaking, no germination yet
            'current_stage_id' => null, // Will be set automatically
        ]);

        // Verify the stage was set automatically to soaking
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('soaking', $crop->getRelation('currentStage')->code);

        // Add germination timestamp (after soaking)
        $crop->germination_at = Carbon::now()->addHours(12);
        $crop->save();

        // Verify the stage updated automatically to germination
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('germination', $crop->getRelation('currentStage')->code);
    }

    public function test_stage_skipping_works()
    {
        // Create a crop and skip blackout stage (go directly from germination to light)
        $recipe = Recipe::factory()->create();
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'germination_at' => Carbon::now(),
            'current_stage_id' => null,
        ]);

        // Verify starting at germination
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('germination', $crop->getRelation('currentStage')->code);

        // Skip blackout and go directly to light
        $crop->light_at = Carbon::now()->addDays(2);
        $crop->save();

        // Verify the stage updated directly to light (skipping blackout)
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('light', $crop->getRelation('currentStage')->code);
    }

    public function test_manual_stage_override_still_works()
    {
        // Create a crop
        $recipe = Recipe::factory()->create();
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'germination_at' => Carbon::now(),
        ]);

        // Verify automatic stage setting
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('germination', $crop->getRelation('currentStage')->code);

        // Manually override the stage (for backward compatibility)
        $lightStage = CropStage::findByCode('light');
        $crop->current_stage_id = $lightStage->id;
        $crop->save();

        // Verify the manual override worked
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('light', $crop->getRelation('currentStage')->code);

        // But if we update a timestamp, it should recalculate automatically
        $crop->harvested_at = Carbon::now()->addDays(7);
        $crop->save();

        // Should now be harvested based on timestamp
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertEquals('harvested', $crop->getRelation('currentStage')->code);
    }

    public function test_crop_creation_with_soaking_sets_correct_initial_stage()
    {
        // Create a recipe that requires soaking
        $recipe = Recipe::factory()->create(['seed_soak_hours' => 8]);

        // Mock the requiresSoaking method to return true
        $recipePartial = \Mockery::mock(Recipe::class)->makePartial();
        $recipePartial->shouldReceive('requiresSoaking')->andReturn(true);
        $recipePartial->id = $recipe->id;
        $recipePartial->seed_soak_hours = 8;

        // Replace the recipe in the database with our mock temporarily
        \Illuminate\Support\Facades\DB::table('recipes')
            ->where('id', $recipe->id)
            ->update(['seed_soak_hours' => 8]);

        // Create a new crop (simulating form submission where requires_soaking gets set)
        $crop = new Crop([
            'recipe_id' => $recipe->id,
            'requires_soaking' => true,
            'tray_number' => '1',
        ]);
        $crop->save();

        // Verify the crop started in soaking stage with timestamp set
        $crop->refresh();
        $crop->load('currentStage');
        $this->assertNotNull($crop->soaking_at);
        $this->assertEquals('soaking', $crop->getRelation('currentStage')->code);
    }
}
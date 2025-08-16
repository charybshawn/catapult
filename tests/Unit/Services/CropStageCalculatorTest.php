<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Crop;
use App\Models\CropStage;
use App\Services\CropStageCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CropStageCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected CropStageCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(CropStageCalculator::class);
        
        // Seed crop stages
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\Lookup\\CropStageSeeder']);
    }

    public function test_calculates_stage_based_on_highest_completed_timestamp()
    {
        $crop = new Crop();
        
        // Test with only germination timestamp
        $crop->germination_at = Carbon::now();
        $stageId = $this->calculator->calculateStageId($crop);
        $stage = CropStage::find($stageId);
        $this->assertEquals('germination', $stage->code);
        
        // Add blackout timestamp - should advance to blackout
        $crop->blackout_at = Carbon::now()->addDays(2);
        $stageId = $this->calculator->calculateStageId($crop);
        $stage = CropStage::find($stageId);
        $this->assertEquals('blackout', $stage->code);
        
        // Add light timestamp - should advance to light
        $crop->light_at = Carbon::now()->addDays(5);
        $stageId = $this->calculator->calculateStageId($crop);
        $stage = CropStage::find($stageId);
        $this->assertEquals('light', $stage->code);
        
        // Add harvested timestamp - should advance to harvested
        $crop->harvested_at = Carbon::now()->addDays(12);
        $stageId = $this->calculator->calculateStageId($crop);
        $stage = CropStage::find($stageId);
        $this->assertEquals('harvested', $stage->code);
    }

    public function test_handles_soaking_stage()
    {
        $crop = new Crop();
        
        // Test with only soaking timestamp
        $crop->soaking_at = Carbon::now();
        $stageId = $this->calculator->calculateStageId($crop);
        $stage = CropStage::find($stageId);
        $this->assertEquals('soaking', $stage->code);
        
        // Add germination - should advance to germination
        $crop->germination_at = Carbon::now()->addHours(24);
        $stageId = $this->calculator->calculateStageId($crop);
        $stage = CropStage::find($stageId);
        $this->assertEquals('germination', $stage->code);
    }

    public function test_defaults_to_germination_when_no_timestamps()
    {
        $crop = new Crop();
        
        $stageId = $this->calculator->calculateStageId($crop);
        $stage = CropStage::find($stageId);
        $this->assertEquals('germination', $stage->code);
    }

    public function test_updates_crop_stage_if_changed()
    {
        $crop = new Crop([
            'current_stage_id' => CropStage::findByCode('germination')->id
        ]);
        
        // Add blackout timestamp
        $crop->blackout_at = Carbon::now();
        
        $wasUpdated = $this->calculator->updateCropStage($crop);
        
        $this->assertTrue($wasUpdated);
        $this->assertEquals('blackout', CropStage::find($crop->current_stage_id)->code);
    }

    public function test_does_not_update_crop_stage_if_unchanged()
    {
        $germinationStage = CropStage::findByCode('germination');
        $crop = new Crop([
            'current_stage_id' => $germinationStage->id
        ]);
        
        $crop->germination_at = Carbon::now();
        
        $wasUpdated = $this->calculator->updateCropStage($crop);
        
        $this->assertFalse($wasUpdated);
        $this->assertEquals($germinationStage->id, $crop->current_stage_id);
    }

    public function test_validates_timestamp_sequence()
    {
        $crop = new Crop();
        
        // Valid sequence
        $crop->soaking_at = Carbon::now();
        $crop->germination_at = Carbon::now()->addDay();
        $crop->blackout_at = Carbon::now()->addDays(3);
        $crop->light_at = Carbon::now()->addDays(6);
        $crop->harvested_at = Carbon::now()->addDays(13);
        
        $errors = $this->calculator->validateTimestampSequence($crop);
        $this->assertEmpty($errors);
        
        // Invalid sequence - blackout before germination
        $crop->blackout_at = Carbon::now()->subDay();
        
        $errors = $this->calculator->validateTimestampSequence($crop);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('blackout', $errors[0]);
        $this->assertStringContainsString('germination', $errors[0]);
    }

    public function test_skips_stages_with_no_timestamps()
    {
        $crop = new Crop();
        
        // Jump from germination directly to light (skip blackout)
        $crop->germination_at = Carbon::now();
        $crop->light_at = Carbon::now()->addDays(2);
        
        $stageId = $this->calculator->calculateStageId($crop);
        $stage = CropStage::find($stageId);
        $this->assertEquals('light', $stage->code);
    }
}
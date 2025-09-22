<?php

namespace App\Console\Commands;

use App\Models\Crop;
use App\Services\CropTimeCalculator;
use Illuminate\Console\Command;

class TestCropTimeCalculation extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'crops:test-time-calculation {crop_id? : ID of a specific crop to test}';

    /**
     * The console command description.
     */
    protected $description = 'Test the crop time calculation system to verify it\'s working correctly';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cropId = $this->argument('crop_id');
        
        if ($cropId) {
            $crop = Crop::with(['recipe', 'currentStage'])->find($cropId);
            if (!$crop) {
                $this->error("Crop with ID {$cropId} not found");
                return 1;
            }
            $crops = collect([$crop]);
        } else {
            // Get a sample of crops
            $crops = Crop::with(['recipe', 'currentStage'])->limit(3)->get();
        }

        if ($crops->isEmpty()) {
            $this->error('No crops found in database');
            return 1;
        }

        $this->info('🧪 Testing crop time calculation system...');
        $this->newLine();

        $timeCalculator = app(CropTimeCalculator::class);

        foreach ($crops as $crop) {
            $this->info("Testing Crop ID: {$crop->id} (Tray: {$crop->tray_number})");
            $this->line("Current Stage: " . ($crop->currentStage->name ?? 'Unknown'));
            $this->line("Recipe: " . ($crop->recipe->name ?? 'No recipe'));
            
            // Show current values
            $this->table(
                ['Field', 'Current Value'],
                [
                    ['Stage Age', $crop->stage_age_display ?? 'NULL'],
                    ['Time to Next Stage', $crop->time_to_next_stage_display ?? 'NULL'],
                    ['Total Age', $crop->total_age_display ?? 'NULL'],
                ]
            );

            // Test the calculation
            $this->info('Running time calculation...');
            
            try {
                // Calculate new values
                $timeCalculator->updateTimeCalculations($crop);
                
                $this->info('✅ Calculation successful!');
                $this->table(
                    ['Field', 'New Value'],
                    [
                        ['Stage Age', $crop->stage_age_display ?? 'NULL'],
                        ['Time to Next Stage', $crop->time_to_next_stage_display ?? 'NULL'],
                        ['Total Age', $crop->total_age_display ?? 'NULL'],
                    ]
                );
                
                // Test saving
                $this->info('Testing save...');
                $crop->save();
                $this->info('✅ Save successful!');
                
            } catch (\Exception $e) {
                $this->error('❌ Calculation failed: ' . $e->getMessage());
                $this->line($e->getTraceAsString());
            }
            
            $this->newLine();
        }

        return 0;
    }
}
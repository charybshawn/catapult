<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Crop;
use App\Observers\CropObserver;
use Illuminate\Support\Facades\DB;

class UpdateCropCalculatedColumns extends Command
{
    protected $signature = 'crops:update-calculated-columns';
    protected $description = 'Update calculated columns for all existing crop records';

    public function handle()
    {
        $this->info('Starting to update calculated columns for all crops...');
        
        $totalCrops = Crop::count();
        $this->output->progressStart($totalCrops);
        
        // Process in chunks to avoid memory issues
        Crop::chunk(100, function ($crops) {
            foreach ($crops as $crop) {
                // Use the observer's method to update the calculated columns
                app(CropObserver::class)->saving($crop);
                
                // Save without triggering the observer again
                DB::table('crops')
                    ->where('id', $crop->id)
                    ->update([
                        'stage_age_minutes' => $crop->stage_age_minutes,
                        'stage_age_display' => $crop->stage_age_display,
                        'time_to_next_stage_minutes' => $crop->time_to_next_stage_minutes,
                        'time_to_next_stage_display' => $crop->time_to_next_stage_display,
                        'total_age_minutes' => $crop->total_age_minutes,
                        'total_age_display' => $crop->total_age_display,
                        'expected_harvest_at' => $crop->expected_harvest_at,
                    ]);
                
                $this->output->progressAdvance();
            }
        });
        
        $this->output->progressFinish();
        $this->info('Successfully updated calculated columns for all crops.');
    }
} 
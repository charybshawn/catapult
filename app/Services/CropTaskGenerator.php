<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\Recipe;
use App\Models\CropTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CropTaskGenerator
{
    /**
     * Generate scheduled tasks for a new crop batch based on its recipe.
     *
     * @param Crop $firstCrop The first representative Crop model of the batch.
     * @param Recipe $recipe The Recipe model used for this batch.
     * @return void
     */
    public function generateTasksForBatch(Crop $firstCrop, Recipe $recipe): void
    {
        if (!$firstCrop->planted_at) {
            Log::error("Cannot generate tasks: Crop ID {$firstCrop->id} missing planted_at timestamp.");
            return;
        }
        
        $plantedAt = Carbon::parse($firstCrop->planted_at); // Ensure it's a Carbon instance
        $tasksToCreate = [];
        Log::debug("Generating CropTasks for Crop ID: {$firstCrop->id}, Planted At: {$plantedAt->toDateTimeString()}, Recipe ID: {$recipe->id}");

        // 1. End of Germination Task
        $germEnd = $plantedAt->copy()->addDays($recipe->germination_days);
        $tasksToCreate[] = [
            'crop_id' => $firstCrop->id,
            'recipe_id' => $recipe->id,
            'task_type' => 'end_germination',
            'details' => json_encode(['target_stage' => ($recipe->blackout_days > 0 ? 'blackout' : 'light')]),
            'scheduled_at' => $germEnd,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        Log::debug("- Scheduled end_germination at: {$germEnd->toDateTimeString()}");

        // 2. End of Blackout Task (if applicable)
        if ($recipe->blackout_days > 0) {
            $blackoutEnd = $germEnd->copy()->addDays($recipe->blackout_days);
            $tasksToCreate[] = [
                'crop_id' => $firstCrop->id,
                'recipe_id' => $recipe->id,
                'task_type' => 'end_blackout',
                'details' => json_encode(['target_stage' => 'light']),
                'scheduled_at' => $blackoutEnd,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
             Log::debug("- Scheduled end_blackout at: {$blackoutEnd->toDateTimeString()}");
        }

        // 3. Expected Harvest Task
        $blackoutEndForHarvest = $germEnd->copy()->addDays($recipe->blackout_days ?? 0);
        $expectedHarvest = $blackoutEndForHarvest->copy()->addDays($recipe->light_days);
        $tasksToCreate[] = [
            'crop_id' => $firstCrop->id,
            'recipe_id' => $recipe->id,
            'task_type' => 'expected_harvest',
            'details' => null,
            'scheduled_at' => $expectedHarvest,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        Log::debug("- Scheduled expected_harvest at: {$expectedHarvest->toDateTimeString()}");

        // 4. Suspend Watering Task (if applicable)
        if ($recipe->suspend_water_hours > 0) {
            $suspendTime = $expectedHarvest->copy()->subHours($recipe->suspend_water_hours);
            if ($suspendTime->isAfter($plantedAt)) {
                 $tasksToCreate[] = [
                    'crop_id' => $firstCrop->id,
                    'recipe_id' => $recipe->id,
                    'task_type' => 'suspend_watering',
                    'details' => null,
                    'scheduled_at' => $suspendTime,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                Log::debug("- Scheduled suspend_watering at: {$suspendTime->toDateTimeString()}");
            } else {
                Log::warning("Suspend watering time ({$suspendTime->toDateTimeString()}) is before planting time ({$plantedAt->toDateTimeString()}) for Crop ID {$firstCrop->id}. Task not generated.");
            }
        }
        
        // Bulk insert tasks if any were generated
        if (!empty($tasksToCreate)) {
            CropTask::insert($tasksToCreate);
            Log::debug("Inserted " . count($tasksToCreate) . " CropTasks for Crop ID: " . $firstCrop->id);
        } else {
             Log::debug("No CropTasks to insert for Crop ID: " . $firstCrop->id);
        }
    }
} 